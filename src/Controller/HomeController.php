<?php

namespace App\Controller;

use App\Entity\SportPlanning;
use App\Repository\SportPlanningRepository;
use App\Service\Session;
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
        private Session $session,
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
        $sportSessions = $this->planningRepository->getNextWeekSessionsByPromotion($promotion);


        if (empty($sportSessions)) {
            return $this->render('home/toosoon.html.twig');
        }

        $currentDate = date('Y-m-d');
        foreach ($sportSessions as $sportSession) {
            $date = $sportSession->getStartingDateTime();
            $diff = $date->diff(new \DateTime($currentDate));

            if ($sportSession->getPlace() === null) {
                $practicePlace = $this->session->setPlace($sportSession, false);
            } else {
                $practicePlace = $sportSession->getPlace();
            }


            $weather = $this->weatherApi->getWeather($sportSession);
            $avgWeather = $this->weatherApi->getAverageWeather($weather);


            return $this->render('home/preview.html.twig', [
                'practicePlace' => $practicePlace,
                'avgWeather' => $avgWeather
            ]);
        }

    }


}
