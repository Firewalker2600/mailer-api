<?php

namespace App\Controller;

use App\Entity\EmailQueue;
use App\Repository\EmailQueueRepository;
use App\Requests\SendMailRequest;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class EmailController extends AbstractController
{
    #[Route('/api/v1/send-email', name: 'send-email', methods: "POST")]
    public function sendEmail(
        LoggerInterface        $logger,
        ValidatorInterface     $validator,
        EmailQueueRepository   $emailQueueRepository,
        EntityManagerInterface $em
    ): JsonResponse
    {
        $sendMailRequest = new SendMailRequest($logger, $validator);
        try {
            $sendMailRequest->validate();
        } catch (ValidationFailedException $exception) {
            return $sendMailRequest->createValidationFailedResponse($exception);
        }
        $emailQueueEntity = (new EmailQueue())
            ->setMessage(json_encode($sendMailRequest->getAll()))
            ->setSent(false)
            ->setCreatedAt(new \DateTime)
            ->setDispatchAt($sendMailRequest->delay_send !== false
                ? \DateTime::createFromFormat('Y-m-d', $sendMailRequest->delay_send->format('Y-m-d'))
                : new \DateTime()
            );
        $emailQueueRepository->save($emailQueueEntity);
        $em->persist($emailQueueEntity);
        $em->flush();

        return new JsonResponse('Email saved for processing', Response::HTTP_ACCEPTED);
    }
}
