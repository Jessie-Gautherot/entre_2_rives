<?php

namespace App\Service;

use App\Entity\BoatModel;
use App\Entity\RentalRate;

/**
 * Prepares rental rates for public display.
 */
class RentalRateDisplayService
{
    /**
     * Groups active rental rates by duration and price.
     * Used to display rates on the boat model page.
     *
     * @param RentalRate[] $rentalRates
     */
    public function groupByDuration(array $rentalRates): array
    {
        $ratesByDuration = [];

        foreach ($rentalRates as $rentalRate) {
            if (!$rentalRate->isActive()) {
                continue;
            }

            $duration = $rentalRate->getDurationHours();

            $ratesByDuration[$duration][] = $rentalRate;
        }

        ksort($ratesByDuration);

        $groupedRates = [];

        foreach ($ratesByDuration as $duration => $rentalRates) {
            $prices = array_unique(
                array_map(
                    fn (RentalRate $rentalRate) => $rentalRate->getPrice(),
                    $rentalRates
                )
            );

            $showLabels = count($prices) > 1;

            $rates = [];

            if ($showLabels) {
                foreach ($rentalRates as $rentalRate) {
                    $rates[] = [
                        'price' => $rentalRate->getPrice(),
                        'label' => $rentalRate->getLabel(),
                    ];
                }
            } else {
                $rates[] = [
                    'price' => $rentalRates[0]->getPrice(),
                    'label' => null,
                ];
            }

            $groupedRates[] = [
                'duration' => $duration,
                'rates' => $rates,
            ];
        }

        return $groupedRates;
    }

    /**
     * Prepares rental rates for several boat models.
     * Used to display active rates on the rates page.
     *
     * @param BoatModel[] $boatModels
     */
    public function prepareByBoatModel(array $boatModels): array
    {
        $pricing = [];

        foreach ($boatModels as $boatModel) {
            $pricing[] = [
                'boatModel' => $boatModel,
                'rates' => $this->groupByDuration(
                    $boatModel->getRentalRates()->toArray()
                ),
            ];
        }

        return $pricing;
    }
}

