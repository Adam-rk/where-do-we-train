<?php

namespace App\Controller;

use App\Entity\SportPlanning;
use App\Repository\SportPlanningRepository;
use Cassandra\Date;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class HomeController extends AbstractController
{
    public function __construct(
        private HttpClientInterface $client,
        private EntityManagerInterface $em,
        private SportPlanningRepository $planningRepository
    )
    {
        $this->planningRepository = $this->em->getRepository(SportPlanning::class);
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
            $date = new \DateTime($sportSession->getDate());

            $diff = $date->diff(new \DateTime($currentDate));

            if ($diff->days <= 7 && $diff->days > 1) {
                $weather = $this->displayWeather($sportSession);
                $canPracticeOutside = $this->canPracticeOutside($weather);
                if ($canPracticeOutside) {
                    $sportSession->setPalce('Stade des Cézeaux');
                } else {
                    $sportSession->setPalce('Hoops Factory');
                }
                $this->em->persist($sportSession);
                $this->em->flush();
            }elseif ($diff->days > 7) {
                return $this->render('home/toosoon.html.twig');
            }

            return $this->render('home/preview.html.twig', [
                'practicePlace' => $sportSession->getPalce(),
            ]);
        }

    }

    private function displayWeather(SportPlanning $sportSession): array {
        $sessionDate = $sportSession->getDate();

        $response = $this->client->request(
            'GET',
            'https://api.open-meteo.com/v1/forecast?latitude=45.78&longitude=3.09&hourly=temperature_2m,precipitation_probability,precipitation&forecast_days=1&start_date='.$sessionDate.'&end_date='.$sessionDate
        );

        $weatherData = $response->toArray();

        $daysAndHours= $weatherData["hourly"]["time"];

        $temperatures = $weatherData["hourly"]["temperature_2m"];

        $precipitationProbabilities = $weatherData["hourly"]["precipitation_probability"];

        $precipitations = $weatherData["hourly"]["precipitation"];

        $clearData = [];

        for ($i = 0; $i < count($daysAndHours); $i++) {
            $hourOfDay = substr($daysAndHours[$i], 11, 5);
            $hourOfDayFormated = DateTime::createFromFormat('H:i', $hourOfDay);



            $startTime = DateTime::createFromFormat('H:i', $sportSession->getStartTime());
            $endTime = DateTime::createFromFormat('H:i', $sportSession->getEndTime());

            if ($hourOfDayFormated >= $startTime && $hourOfDayFormated <= $endTime) {
                $clearData[$hourOfDay] = [
                    "temperature" => $temperatures[$i],
                    "precipitation_probability" => $precipitationProbabilities[$i],
                    "precipitation" => $precipitations[$i]
                ];
            }

        }
        return $clearData;
    }

    private function canPracticeOutside(array $weather) {
        foreach ($weather as $hourlyWeather) {
            if ($hourlyWeather["temperature"] < 10 || $hourlyWeather["precipitation_probability"] > 50 || $hourlyWeather["precipitation"] > 0) {
                return false;
            }

            return true;
        }
    }
}
