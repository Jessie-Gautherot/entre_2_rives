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
            $price = $rentalRate->getPrice();

            $ratesByDuration[$duration][$price][] = $rentalRate->getLabel();
        }

        ksort($ratesByDuration);

        $groupedRates = [];

        foreach ($ratesByDuration as $duration => $prices) {
            $showLabels = count($prices) > 1;

            $rates = [];

            foreach ($prices as $price => $labels) {
                $rates[] = [
                    'price' => $price,
                    'label' => $showLabels
                        ? implode(' / ', $labels)
                        : null,
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