<?php

namespace App\Service;

use App\Entity\SportPlanning;
use DateTime;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class WeatherApi
{
    public function __construct(
        private HttpClientInterface $weatherClient,
        private UrlGeneratorInterface $router
    )
    {
    }
    public function getWeather(SportPlanning $sportSession): array {
        $sessionDate = $sportSession->getStartingDateTime();

        $apiUrl = $this->router->generate('weather_client', [
            'latitude' => 45.78,
            'longitude' => 3.09,
            'hourly' => 'temperature_2m,precipitation_probability,precipitation',
            'forecast_days' => 1,
            'start_date' => $sessionDate->format('Y-m-d'),
            'end_date' => $sessionDate->format('Y-m-d')
        ]);

        $response = $this->weatherClient->request(
            'GET',
            $apiUrl
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



            $startTime = $sportSession->getStartingDateTime();
            $endTime = $sportSession->getEndingDateTime();

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
}