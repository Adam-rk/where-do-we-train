<?php

namespace App\Service;

use App\Entity\SportPlanning;
use App\Repository\SportPlanningRepository;
use Doctrine\ORM\EntityManagerInterface;

class Session
{
    public function __construct(
        private WeatherApi $weatherApi,
        private EntityManagerInterface $em
    )
    {

    }

    public function setPlace(SportPlanning $sportSession, bool $flush = true) {
        $weather = $this->weatherApi->getWeather($sportSession);
        $canPracticeOutside = $this->canPracticeOutside($weather);
 
        if ($flush) {
            if ($canPracticeOutside) {
                $sportSession->setPlace('Stade des Cézeaux');
            } else {
                $sportSession->setPlace('Hoops Factory');
            }
            $this->em->persist($sportSession);
            $this->em->flush();
        } else {
            if ($canPracticeOutside) {
                return 'Stade des Cézeaux';
            }
            return 'Hoops Factory';
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
}