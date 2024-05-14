<?php

namespace App\Controller;

use App\Form\CityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

class CityControllerFinland extends AbstractController
{
    private $client;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    #[Route("/", name: "daylight")]
    public function index(Request $request): Response
    {
        $form = $this->createForm(CityType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $cityName = $form->get("city")->getData();
            $cityName2 = $form->get("city2")->getData();

            // Fetch latitude and longitude for the entered city name - WORKING
            $coordinates = $this->getCoordinatesForCity($cityName);
            $coordinates2 = $this->getCoordinatesForCity($cityName2);

            if ($coordinates) {
                // Calculate the change in daylight length in minutes
                $daylightData = $this->calculateDaylightChanges($coordinates);
                $daylightData2 = $this->calculateDaylightChanges($coordinates2);

                // Render the results with the daylight data
                return $this->render("city/show.html.twig", [
                    "form" => $form->createView(),
                    "daylightChanges" => $daylightData["daylightChanges"],
                    "daylightChanges2" => $daylightData2["daylightChanges"],
                    "cityName" => $cityName,
                    "cityName2" => $cityName2 
                   
                ]);
            } else {
                // Handle the case where coordinates could not be found
                $this->addFlash(
                    "error",
                    "Could not find the coordinates for the entered city. Please try a different city."
                );
            }

            // CITY 2

            if ($coordinates2) {
                // Calculate the change in daylight length in minutes
                $daylightData2 = $this->calculateDaylightChanges($coordinates2);

                // Render the results with the daylight data
                return $this->render("city/show.html.twig", [
                    "form" => $form->createView(),
                    "daylightChanges" => $daylightData2["daylightChanges"],
                    "cityName" => $cityName2,
                   
                ]);
            } else {
                // Handle the case where coordinates could not be found
                $this->addFlash(
                    "error",
                    "Could not find the coordinates for the entered city. Please try a different city."
                );
            }
        }

        return $this->render("city/index.html.twig", [
            "form" => $form->createView(),
        ]);
    }

    #[Route("/api/daylight/{cityName}", name: "api_daylight")]
    public function daylightApi(
        Request $request,
        string $cityName
    ): JsonResponse {
        $coordinates = $this->getCoordinatesForCity($cityName);

        if ($coordinates) {
            $daylightChanges = $this->calculateDaylightChanges($coordinates);
            return $this->json([
                "daylightChanges" => $daylightChanges,
                "daylightChanges2" => $daylightChanges2,
                "cityName" => $cityName,
                "cityName2" => $cityName2,
            ]);
        } else {
            return $this->json(
                [
                    "error" =>
                        "Could not find the coordinates for the entered city.",
                ],
                Response::HTTP_NOT_FOUND
            );
        }
    }

    // City 2

    #[Route("/api/daylight/{cityName2}", name: "api_daylight2")]
    public function daylightApi2(
        Request $request,
        string $cityName2
    ): JsonResponse {
        $coordinates2 = $this->getCoordinatesForCity($cityName2);

        if ($coordinates2) {
            $daylightChanges2 = $this->calculateDaylightChanges($coordinates2);
            return $this->json([
                "daylightChanges" => $daylightChanges2,
                "cityName" => $cityName2,
            ]);
        } else {
            return $this->json(
                [
                    "error" =>
                        "Could not find the coordinates for the entered city.",
                ],
                Response::HTTP_NOT_FOUND
            );
        }
    }
    


    // WORKING
    private function getCoordinatesForCity($cityName): ?array
    {
        $apiKey = $this->getParameter("geocode_api_key"); // Use the 'geocode_api_key' parameter

        $geocodeResponse = $this->client->request(
            "GET",
            "https://geocode.maps.co/search",
            [
                "query" => [
                    "q" => $cityName,
                    "api_key" => $apiKey, // Use the API key from the .env file
                ],
            ]
        );

        $geocodeData = $geocodeResponse->toArray();

        if (
            !empty($geocodeData) &&
            isset($geocodeData[0]["lat"], $geocodeData[0]["lon"])
        ) {
            return [
                "lat" => $geocodeData[0]["lat"],
                "lng" => $geocodeData[0]["lon"],
            ];
        }

        return null;
    }

    // City 2 - Working

    private function getCoordinatesForCity2($cityName2): ?array
    {
        $apiKey = $this->getParameter("geocode_api_key"); // Use the 'geocode_api_key' parameter

        $geocodeResponse = $this->client->request(
            "GET",
            "https://geocode.maps.co/search",
            [
                "query" => [
                    "q" => $cityName,
                    "api_key" => $apiKey, // Use the API key from the .env file
                ],
            ]
        );

        $geocodeData = $geocodeResponse->toArray();

        if (
            !empty($geocodeData) &&
            isset($geocodeData[0]["lat"], $geocodeData[0]["lon"])
        ) {
            return [
                "lat" => $geocodeData[0]["lat"],
                "lng" => $geocodeData[0]["lon"],
            ];
        }

        return null;
    }



    private function calculateDaylightChanges($coordinates): array
{
    $daylightChanges = [];
    $startDate = new \DateTime('first day of January this year');
    $endDate = new \DateTime('last day January this year');


    for($date = $startDate; $date <= $endDate; $date->modify('+1 day')) {
        $daylightData = $this->fetchDaylightData($coordinates, $date->format('Y-m-d'));

        if (!empty($daylightData["results"])) {
            // Convert sunrise and sunset to local time zone
            $sunriseLocal = (new \DateTime(
                $daylightData["results"]["sunrise"],
                new \DateTimeZone("UTC")
            ))
                ->setTimezone(new \DateTimeZone("Europe/Helsinki"))
                ->format("H:i:s");
            $sunsetLocal = (new \DateTime(
                $daylightData["results"]["sunset"],
                new \DateTimeZone("UTC")
            ))
                ->setTimezone(new \DateTimeZone("Europe/Helsinki"))
                ->format("H:i:s");

            $dayLength = (new \DateTime($sunsetLocal))->diff(
                new \DateTime($sunriseLocal)
            );
            $daylightChanges[$date->format('Y-m-d')] = $dayLength->format(
                "%h hours %i minutes"
            );

        }
    }
    // Return the daylight changes along with the local sunrise and sunset times
    return [
        "daylightChanges" => $daylightChanges,
    ];
}


//City 2
    private function calculateDaylightChanges2($coordinates2): array
{
    $daylightChanges2 = [];
    $startDate = new \DateTime('first day of January this year');
    $endDate = new \DateTime('last day January this year');

    $sunriseLocal2 = "";
    $sunsetLocal2 = "";
    $timeLeftForSunset2 = ""; // Initialize the variable

    for($date = $startDate; $date <= $endDate; $date->modify('+1 day')) {
        $daylightData = $this->fetchDaylightData($coordinates2, $date->format('Y-m-d'));

        if (!empty($daylightData2["results"])) {
            // Convert sunrise and sunset to local time zone
            $sunriseLocal2 = (new \DateTime(
                $daylightData2["results"]["sunrise"],
                new \DateTimeZone("UTC")
            ))
                ->setTimezone(new \DateTimeZone("Europe/Helsinki"))
                ->format("H:i:s");
            $sunsetLocal2 = (new \DateTime(
                $daylightData2["results"]["sunset"],
                new \DateTimeZone("UTC")
            ))
                ->setTimezone(new \DateTimeZone("Europe/Helsinki"))
                ->format("H:i:s");

            $dayLength2 = (new \DateTime($sunsetLocal2))->diff(
                new \DateTime($sunriseLocal2)
            );
            $daylightChanges2[$date->format('Y-m-d')] = $dayLength2->format(
                "%h hours %i minutes"
            );

        }
    }
    // Return the daylight changes along with the local sunrise and sunset times
    return [
        "daylightChanges" => $daylightChanges2,
    ];
}




    private function fetchDaylightData($coordinates, $date): array
    {
        $response = $this->client->request(
            "GET",
            "https://api.sunrise-sunset.org/json",
            [
                "query" => [
                    "lat" => $coordinates["lat"],
                    "lng" => $coordinates["lng"],
                    "date" => $date,
                    "formatted" => 0,
                ],
            ]
        );

        return $response->toArray();
    }

    // City 2
    private function fetchDaylightData2($coordinates2, $date): array
    {
        $response = $this->client->request(
            "GET",
            "https://api.sunrise-sunset.org/json",
            [
                "query" => [
                    "lat" => $coordinates2["lat"],
                    "lng" => $coordinates2["lng"],
                    "date" => $date,
                    "formatted" => 0,
                ],
            ]
        );
    
        return $response->toArray();
    }
}

