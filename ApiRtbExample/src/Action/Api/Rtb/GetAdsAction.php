<?php

namespace App\Action\Api\Rtb;

use App\Domain\Api\Rtb\AdsRequester;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use App\Factory\ValidationFactory;
use Cake\Validation\Validator;
use Selective\Validation\Exception\ValidationException;

/**
 * Action.
 * Get request's arguments, validate it. Invoke the Domain with inputs and retain the result.
 */
final class GetAdsAction
{
    private AdsRequester $adsRequester;
    private ValidationFactory $validationFactory;


    /**
     * The constructor.
     *
     * @param AdsRequester $adsRequester The domain
     * @param ValidationFactory $validationFactory The validation
     */
    public function __construct(AdsRequester $adsRequester, ValidationFactory $validationFactory)
    {
        $this->adsRequester = $adsRequester;
        $this->validationFactory = $validationFactory;
    }

    /**
     * Action.
     *
     * @param ServerRequestInterface $request The request
     * @param ResponseInterface $response The response
     * @param $args The arhuments from route
     *
     * @return ResponseInterface The response
     */
    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {

        // get agruments from requtes
        //$args = RouteContext::fromRequest($request)>getRoute()->getArguments();

        // create validator
        $validator = $this->createValidator();

        $validationResult = $this->validationFactory->createValidationResult(
            $validator->validate($args)
        );

        if ( $validationResult->fails() ) {
            throw new ValidationException('Error: Please check your input', $validationResult);
        }

        $mode = ( (!empty($args['mode']) && $args['mode'] == 'test') ? $args['mode'] : '' );

        // Invoke the Domain with inputs and retain the result
        $result = $this->adsRequester->requestAds( $args['brokerId'], $args['width'], $args['height'], $args['bidfloor'], $mode ) ;

        // Build the HTTP response
        $response->getBody()->write($result);

        return $response;
    }

    /**
     * Create validator.
     *
     * @return Validator The validator
     */
    private function createValidator(): Validator
    {
        $validator = $this->validationFactory->createValidator();

        return $validator
            ->requirePresence('brokerId', 'This field is required')
            ->notEmptyString('brokerId', 'brokerId is required')
            ->minLength('brokerId', 3, 'Too short')
            ->maxLength('brokerId', 3, 'Too long')
            ->requirePresence('width', 'This field is required')
            ->integer('width', 'Invalid width')
            ->requirePresence('height', 'This field is required')
            ->integer('height', 'Invalid height')
            ->requirePresence('bidfloor', 'This field is required')
            ->decimal('bidfloor',2, 'Invalid bidfloor');
    }
}