<?php

namespace App\Tests\Boat;

use App\Entity\BoatModel;
use App\Entity\RentalRate;
use App\Service\RentalRateDisplayService;
use PHPUnit\Framework\TestCase;

class RentalRateDisplayServiceTest extends TestCase
{
    /**
     * Create a rental rate with the data needed for the test.
     */
    private function createRate(
        string $label,
        int $duration,
        int $price,
        bool $isActive = true
    ): RentalRate {
        $rate = new RentalRate();

        $rate->setLabel($label);
        $rate->setDurationHours($duration);
        $rate->setPrice($price);
        $rate->setIsActive($isActive);

        return $rate;
    }

    /**
     * Check that active rental rates are grouped by duration and price.
     */
    public function testGroupByDuration(): void
    {
        $service = new RentalRateDisplayService();

        // Create two 2-hour rates with the same price.
        $rate1 = $this->createRate(
            '2 hours morning',
            2,
            5000
        );

        $rate2 = $this->createRate(
            '2 hours afternoon',
            2,
            5000
        );

        // Create two 4-hour rates with different prices.
        $rate3 = $this->createRate(
            'Half-day morning',
            4,
            9000
        );

        $rate4 = $this->createRate(
            'Half-day afternoon',
            4,
            10000
        );

        // Create an inactive rate that must be ignored.
        $rate5 = $this->createRate(
            'Full day',
            9,
            15000,
            false
        );

        // Group the rates in an unsorted order.
        $groupedRates = $service->groupByDuration([
            $rate3,
            $rate1,
            $rate5,
            $rate4,
            $rate2,
        ]);

        // Check that the inactive rate is ignored.
        self::assertCount(2, $groupedRates);

        // Check the 2-hour group with one common price.
        self::assertSame(2, $groupedRates[0]['duration']);
        self::assertSame(5000, $groupedRates[0]['rates'][0]['price']);
        self::assertNull($groupedRates[0]['rates'][0]['label']);

        // Check the 4-hour group with two different prices and labels.
        self::assertSame(4, $groupedRates[1]['duration']);
        self::assertSame(9000, $groupedRates[1]['rates'][0]['price']);
        self::assertSame(
            'Half-day morning',
            $groupedRates[1]['rates'][0]['label']
        );
        self::assertSame(10000, $groupedRates[1]['rates'][1]['price']);
        self::assertSame(
            'Half-day afternoon',
            $groupedRates[1]['rates'][1]['label']
        );
    }

    /**
     * Check that rental rates are prepared for each boat model.
     */
    public function testPrepareByBoatModel(): void
    {
        $service = new RentalRateDisplayService();

        // Create Model A with one 2-hour rate.
        $modelA = new BoatModel();
        $modelA->setName('Model A');

        $rateA = $this->createRate(
            '2 hours',
            2,
            5000
        );

        $modelA->addRentalRate($rateA);

        // Create Model B with one 4-hour rate.
        $modelB = new BoatModel();
        $modelB->setName('Model B');

        $rateB = $this->createRate(
            'Half-day',
            4,
            9000
        );

        $modelB->addRentalRate($rateB);

        // Prepare the rates for both boat models.
        $pricing = $service->prepareByBoatModel([
            $modelA,
            $modelB,
        ]);

        // Check that both models have their prepared rates.
        self::assertCount(2, $pricing);

        self::assertSame($modelA, $pricing[0]['boatModel']);
        self::assertSame(2, $pricing[0]['rates'][0]['duration']);

        self::assertSame($modelB, $pricing[1]['boatModel']);
        self::assertSame(4, $pricing[1]['rates'][0]['duration']);
    }
}