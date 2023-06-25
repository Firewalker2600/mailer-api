<?php

namespace App\Command;

use App\Repository\EmailQueueRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;

#[AsCommand(
    name: 'mailer:dispatch',
    description: 'Dispatch due queued email messages',
)]
class MailerDispatchCommand extends Command
{
    const TRANSPORT_FAILED_MESSAGE = "Email transport failed ";
    const ENTITY_MANAGER_PERSISTENCE_FAILED_MESSAGE = "Failed to persist entity EmailQueue in the database ";
    const ENTITY_MANAGER_FLUSH_FAILED_MESSAGE = "Failed to flush EntityManager ";
    public function __construct(protected EmailQueueRepository $emailQueueRepository, protected EntityManagerInterface $em, protected MailerInterface $mailer, protected LoggerInterface $logger)
    {
        parent::__construct('mailer:dispatch');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $messages = $this->emailQueueRepository->findUnsentMessages();
        foreach ($messages as $message) {
            /** @var  TemplatedEmail $email */
            $parameters = json_decode($message->getMessage(), true);
            $email = $this->buildEmail($parameters);
            try {
                $this->mailer->send($email);
                $message->setSent(true);
                $this->em->persist($message);
            } catch (TransportExceptionInterface $e) {
                $this->logger->error(self::TRANSPORT_FAILED_MESSAGE, ["message" => $e->getMessage()]);
                $io->error(self::TRANSPORT_FAILED_MESSAGE .  $e->getMessage());
                return Command::FAILURE;
            }
        }
        $this->em->flush();
        $count = count($messages);
        $successMessage = $count . " email" . $count > 1 ? "s" : "" . " sent successfully";
        $io->success($successMessage);

        return Command::SUCCESS;
    }

    /**
     * @param array{"body_data":array{"id":string, "date":string, "link":array{"url":string, "label":string}}, "key":string, "subject" : string, "email":string|array<string>, "bcc":null|string|array<string>} $parameters
     * @return TemplatedEmail
     */
    protected function buildEmail(array $parameters): TemplatedEmail
    {
        $data = $parameters["body_data"];
        $email = new TemplatedEmail();
        $email
            ->to($parameters["email"])
            ->subject($parameters["subject"])
            ->getHeaders()->addTextHeader('X-Auto-Response-Suppress', 'OOF, DR, RN, NRN, AutoReply');
        if($parameters["bcc"]) {
            $email->bcc($parameters["bcc"]);
        }

        $email
            ->htmlTemplate("email/{$parameters["key"]}.html.twig")
            ->context([
                "id" => $data['id'],
                "number" => \DateTime::createFromFormat('Y-m-d', $data['date'])->diff(new \DateTime())->days,
                "link" => $data['link']
            ])
        ;
        return $email;
    }
}
