<?php

namespace App\Action;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;
use App\Domain\Services\CurlRequester;
use App\Domain\Delivery\DeliveryCalculator;

final class HomeAction
{

    /**
     * @var ContainerInterface $container
     */
    private ContainerInterface $container;

    /**
     * The constructor
     *
     * @var ContainerInterface $container
     *
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * The action for home url
     * Validates input data, calls domain to get business data and prepares response.
     *
     * @var ServerRequestInterface $request - The request
     * @var ResponseInterface $response - The response
     *
     * @return ResponseInterface
     */
    public function __invoke(Request $request, Response $response): Response
    {

        // Delivery requests examples
        $deliveries = [
            ['sourceKladr'=> 'Moscow', 'targetKladr'=> 'S-Peterburg', 'weight' => 10.3, 'company' => 'fastDelivery'],
            ['sourceKladr'=> 'Moscow', 'targetKladr'=> 'S-Peterburg', 'weight' => 10.3, 'company' => 'slowDelivery'],
            ['sourceKladr'=> 'Kaluga', 'targetKladr'=> 'Tula', 'weight' => 5.0, 'company' => 'slowDelivery'],
            ['sourceKladr'=> 'Karaganda', 'targetKladr'=> 'Omsk', 'weight' => 100.0, 'company' => 'fastDelivery'],
            ['sourceKladr'=> 'Khabarovsk', 'targetKladr'=> 'Vladivostok', 'weight' => 30.560, 'company' => 'slowDelivery'],

        ];

        $response->getBody()->write('Delivery calculation result<br>');

        // calculate delivery price and time for delivery requests and prepare response.
        foreach ($deliveries as $delivery) {
            $class = $this->container->get('settings')['deliveryCompanies'][$delivery['company']];
            $answer = (
                new DeliveryCalculator(
                    new $class(CurlRequester::getInstance())
                )
            )->calculateDeliveryParams($delivery['sourceKladr'], $delivery['targetKladr'],$delivery['weight'] );
            $response->getBody()->write(
                "<p> Delivery: from " . $delivery['sourceKladr'] .
                "; to ".$delivery['targetKladr'] .
                "; weight ".$delivery['weight'] . "kg" .
                "</p>");
            if ($answer->error <> '') {
                $response->getBody()->write("Delivery cannot be calculated due to error: " . $answer->error);
            } else {
                $response->getBody()->write(
                    "<p>Delivery company: " . $delivery['company'] .
                    sprintf("; price: %.2f", $answer->price) . " RUB" .
                    "; expected date:" . $answer->date . " </p>"
                );
            }
            $response->getBody()->write("<hr>");
        }

        return $response;
    }
}