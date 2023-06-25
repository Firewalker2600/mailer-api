<?php

namespace App\Requests;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class ApiRequest
{
    public function __construct(public LoggerInterface $logger, public ValidatorInterface $validator)
    {
        $this->populate();
    }

    public function validate(): void
    {
        $errors = $this->validator->validate($this);

        if($errors->count() > 0) {
            throw new ValidationFailedException('validation_failed', $errors);
        }
    }

    public function getRequest(): Request
    {
        return Request::createFromGlobals();
    }

    protected function populate(): void
    {
        $requestFields =$this->getRequest()->toArray();
        $this->logger->info('API request made', [$requestFields]);
        $reflection = new \ReflectionClass($this);
        foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            if (array_key_exists($property->getName(), $requestFields)) {
                $this->{$property->getName()} = $requestFields[$property->getName()];
            }
        }
    }

    public function createValidationFailedResponse(ValidationFailedException $exception): JsonResponse
    {
        $messages = ['message' => 'Attribute validation failed.', 'errors' => []];

        /** @var ConstraintViolation $constraintViolation  */
        foreach ($exception->getViolations() as $constraintViolation) {
            $messages['errors'][] = [
                'property' => $constraintViolation->getPropertyPath(),
                'value' => $constraintViolation->getInvalidValue(),
                'message' => $constraintViolation->getMessage(),
            ];
        }

        return new JsonResponse($messages, Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
