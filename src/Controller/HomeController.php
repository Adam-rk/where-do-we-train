<?php

namespace App\Controller;

use App\Entity\SportPlanning;
use App\Repository\SportPlanningRepository;
use App\Service\WeatherApi;
use Cassandra\Date;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Router;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class HomeController extends AbstractController
{
    public function __construct(
        private WeatherApi $weatherApi,
        private EntityManagerInterface $em,
        private SportPlanningRepository $planningRepository
    )
    {

    }

    #[Route('/', name: 'app_home')]
    public function home(): Response
    {
        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }
    #[Route('/preview/{promotion}', name: 'preview')]
    public function preview($promotion): Response {
        $sportSessions = $this->planningRepository->findBy(['promotion' => $promotion]);

        if (empty($sportSessions)) {
            return $this->render('home/nosessions.html.twig');
        }

        $currentDate = date('Y-m-d');
        foreach ($sportSessions as $sportSession) {
            $date = $sportSession->getStartingDateTime();
            $diff = $date->diff(new \DateTime($currentDate));

            if ($diff->days <= 7 && $diff->days > 1) {
                $weather = $this->weatherApi->getWeather($sportSession);
                $this->getAverageWeather($weather);
                $canPracticeOutside = $this->canPracticeOutside($weather);
                if ($canPracticeOutside) {
                    $sportSession->setPlace('Stade des Cézeaux');
                } else {
                    $sportSession->setPlace('Hoops Factory');
                }
                $this->em->persist($sportSession);
                $this->em->flush();
            }elseif ($diff->days > 7) {
                return $this->render('home/toosoon.html.twig');
            }

            $avgWeather = $this->getAverageWeather($weather);


            return $this->render('home/preview.html.twig', [
                'practicePlace' => $sportSession->getPlace(),
                'avgWeather' => $avgWeather
            ]);
        }

    }

    private function canPracticeOutside(array $weather) {
        foreach ($weather as $hourlyWeather) {
            if ($hourlyWeather["temperature"] < 10 || $hourlyWeather["precipitation_probability"] > 50 || $hourlyWeather["precipitation"] > 0) {
                return false;
            }
            return true;
        }
    }

    private function getAverageWeather(array $weather): array {
        $avgTemperature = 0;
        $avgPrecipitation = 0;
        $avgPrecipitationProbability = 0;
        foreach ($weather as $hourlyWeather) {
            $avgTemperature += $hourlyWeather["temperature"];
            $avgPrecipitation += $hourlyWeather["precipitation"];
            $avgPrecipitationProbability += $hourlyWeather["precipitation_probability"];
        }
        $avgTemperature /= count($weather);
        $avgPrecipitation /= count($weather);
        $avgPrecipitationProbability /= count($weather);

        $avgWeather = [
            "avg_temp" => round($avgTemperature),
            "avg_precipitation" => round($avgPrecipitation),
            "avg_precipitation_proba" => round($avgPrecipitationProbability)
        ];
        return $avgWeather;
    }
}
