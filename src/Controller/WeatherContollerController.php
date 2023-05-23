<?php

namespace App\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class WeatherContollerController extends AbstractController
{
    public function __construct(
        private HttpClientInterface $client,
    ) {
    }

    private function fetchWeatherApi (): array {
        $response = $this->client->request(
            'GET',
            'https://api.open-meteo.com/v1/forecast?latitude=45.78&longitude=3.09&hourly=temperature_2m,precipitation_probability,precipitation&forecast_days=3'
        );

        return $response->toArray();
    }
    #[Route('/test', name: 'app_weather_contoller')]
    public function index(): JsonResponse
    {
        $weatherData = $this->fetchWeatherApi();

        $daysAndHours= $weatherData["hourly"]["time"];

        $temperatures = $weatherData["hourly"]["temperature_2m"];

        $precipitationProbabilities = $weatherData["hourly"]["precipitation_probability"];

        $precipitations = $weatherData["hourly"]["precipitation"];

        $clearData = [];

        for ($i = 0; $i < count($daysAndHours); $i++) {

            $clearData[$daysAndHours[$i]] = [
                "temperature" => $temperatures[$i],
                "precipitation_probability" => $precipitationProbabilities[$i],
                "precipitation" => $precipitations[$i]
            ];
        }

        print_r($clearData);

        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/WeatherContollerController.php',
        ]);
    }
}
