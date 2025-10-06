<?php

namespace App\Command;

use App\Entity\Dokument;
use App\Service\DokumentService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:execute-document',
    description: 'Execute a document action (grant/revoke roles)',
)]
class ExecuteDocumentCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private DokumentService $dokumentService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('document-id', InputArgument::REQUIRED, 'Document ID to execute');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $documentId = $input->getArgument('document-id');

        $dokument = $this->entityManager->getRepository(Dokument::class)->find($documentId);

        if (!$dokument) {
            $io->error("Document $documentId not found");
            return Command::FAILURE;
        }

        $io->section("Document Info");
        $io->text("ID: {$dokument->getId()}");
        $io->text("Type: {$dokument->getTyp()}");
        $io->text("Status: {$dokument->getStatus()}");
        $io->text("Member: " . ($dokument->getCzlonek() ? $dokument->getCzlonek()->getFullName() : 'N/A'));

        try {
            $this->dokumentService->executeDocumentAction($dokument);
            $this->entityManager->refresh($dokument);

            $io->success("Document action executed successfully");
            $io->text("New status: {$dokument->getStatus()}");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error("Failed to execute document action: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
