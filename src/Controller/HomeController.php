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

        $currentDate = date('Y-m-d');
        foreach ($sportSessions as $sportSession) {
            $date = new \DateTime($sportSession->getDate());

            $diff = $date->diff(new \DateTime($currentDate));

            if ($diff->days <= 7) {
                $this->displayWeather($sportSession);
            }
        }

    return new Response("All good man");

    }

    private function displayWeather(SportPlanning $sportSession) {
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

            if ($hourOfDay <= $endTime && $startTime <= $hourOfDayFormated) {
                $clearData[$hourOfDay] = [
                    "temperature" => $temperatures[$i],
                    "precipitation_probability" => $precipitationProbabilities[$i],
                    "precipitation" => $precipitations[$i]
                ];
            }

        }
        var_dump($clearData);

    }
}
