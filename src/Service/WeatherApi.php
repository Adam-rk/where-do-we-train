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

        [
            'hourly' => [
                'time' => $daysAndHours,
                'temperature_2m' => $temperatures,
                'precipitation_probability' => $precipitationProbabilities,
                'precipitation' => $precipitations
            ]
        ] = $weatherData;

        $clearData = [];

        $startTime = $sportSession->getStartingDateTime()->format('H:i');
        $endTime = $sportSession->getEndingDateTime()->format('H:i');

        foreach ($daysAndHours as $i => $dayAndHour) {
            $hourOfDay = substr($dayAndHour, 11, 5);
            $hourOfDayFormated = DateTime::createFromFormat('H:i', $hourOfDay)->format('H:i');


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

    public function getAverageWeather(array $weather): array {
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

