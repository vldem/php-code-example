<?php

namespace App\Action\Api\Offer;

use App\Domain\Api\Offer\OfferMessageSender;
use App\Domain\Api\Offer\OfferMessage;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use App\Factory\ValidationFactory;
use Cake\Validation\Validator;
use Selective\Validation\Exception\ValidationException;

/**
 * Action.
 * Get request's arguments, validate it. Invoke the Domain with inputs and retain the result.
 */
final class CreateOfferMessageAction
{
    private OfferMessageSender $offerMessageSender;
    private ValidationFactory $validationFactory;
    private OfferMessage $offerMessage;


    /**
     * The constructor.
     *
     * @param OfferMessageSender $offerMessageSender The domain
     * @param ValidationFactory $validationFactory The validation
     * @param OfferMessage $offerMessage The offer messages
     *
     */
    public function __construct( OfferMessageSender $offerMessageSender, ValidationFactory $validationFactory, OfferMessage $offerMessage )
    {
        $this->offerMessageSender = $offerMessageSender;
        $this->validationFactory = $validationFactory;
        $this->offerMessage = $offerMessage;
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

        // create validator
        $validator = $this->createValidator();

        // get validation result
        $validationResult = $this->validationFactory->createValidationResult(
            $validator->validate($args)
        );

        if ( $validationResult->fails() ) {
            throw new ValidationException('Error: Please check your input', $validationResult);
        }

        // Invoke the Domain with inputs and retain the result
        $result = $this->offerMessageSender->send( $args['brokerId'] ) ;

        // Build the HTTP response
        $response->getBody()->write( $this->offerMessage->getMessage( 'successOfferLoadMessage', array('{brokerId}'), array($result->brokerId) ) );

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
            ->minLength('brokerId', 2, 'Too short')
            ->maxLength('brokerId', 3, 'Too long');
    }
}