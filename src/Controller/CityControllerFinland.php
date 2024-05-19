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

            // Fetch latitude and longitude
            $coordinates = $this->getCoordinatesForCity($cityName);
            $coordinates2 = $this->getCoordinatesForCity($cityName2);

            $daylightData = $coordinates
                ? $this->calculateDaylightChanges($coordinates)
                : null;
            $daylightData2 = $coordinates2
                ? $this->calculateDaylightChanges($coordinates2)
                : null;

            if (!$daylightData || !$daylightData2) {
                $this->addFlash(
                    "error",
                    "Could not find the coordinates for the entered city. Please try a different city."
                );
            }

            // Render the results 
            return $this->render("city/show.html.twig", [
                "form" => $form->createView(),
                "daylightChanges" => $daylightData["daylightChanges"] ?? [],
                "daylightChanges2" => $daylightData2["daylightChanges"] ?? [],
                "cityName" => $cityName,
                "cityName2" => $cityName2,
            ]);
        }

        return $this->render("city/index.html.twig", [
            "form" => $form->createView(),
        ]);
    }

    #[Route("/api/daylight/{cityName}/{cityName2}", name: "api_daylight")]
    public function daylightApi(
        Request $request,
        string $cityName,
        string $cityName2
    ): JsonResponse {
        $coordinates = $this->getCoordinatesForCity($cityName);
        $coordinates2 = $this->getCoordinatesForCity($cityName2);

        if ($coordinates && $coordinates2) {
            $daylightChanges = $this->calculateDaylightChanges($coordinates);
            $daylightChanges2 = $this->calculateDaylightChanges($coordinates2);

            return $this->json([
                "daylightChanges" => $daylightChanges,
                "cityName" => $cityName,
                "daylightChanges2" => $daylightChanges2,
                "cityName2" => $cityName2,
            ]);
        } else {
            $errorMessages = [];
            if (!$coordinates) {
                $errorMessages[
                    "city1Error"
                ] = "Could not find the coordinates for the city: {$cityName}.";
            }
            if (!$coordinates2) {
                $errorMessages[
                    "city2Error"
                ] = "Could not find the coordinates for the city: {$cityName2}.";
            }
            return $this->json($errorMessages, Response::HTTP_NOT_FOUND);
        }
    }

    private function getCoordinatesForCity($cityName): ?array
    {
        $apiKey = $this->getParameter("geocode_api_key"); 

        $geocodeResponse = $this->client->request(
            "GET",
            "https://geocode.maps.co/search",
            [
                "query" => [
                    "q" => $cityName,
                    "api_key" => $apiKey, 
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
        $startDate = new \DateTime("first day of January this year");
        $endDate = new \DateTime("last day December this year");

        for ($date = $startDate; $date <= $endDate; $date->modify("+1 day")) {
            $daylightData = $this->fetchDaylightData(
                $coordinates,
                $date->format("Y-m-d")
            );

            if (!empty($daylightData["results"])) {
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
                $daylightChanges[$date->format("Y-m-d")] = $dayLength->format(
                    "%h hours %i minutes"
                );
            }
        }
        return [
            "daylightChanges" => $daylightChanges,
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
}