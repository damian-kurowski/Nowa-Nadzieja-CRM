<?php

namespace App\Command;

use App\Document\DocumentFactory;
use App\Repository\DokumentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:regenerate-document',
    description: 'Regenerate document content with current template logic',
)]
class RegenerateDocumentCommand extends Command
{
    public function __construct(
        private DokumentRepository $dokumentRepository,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('document_id', InputArgument::REQUIRED, 'ID of document to regenerate');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $documentId = $input->getArgument('document_id');

        $dokument = $this->dokumentRepository->findWithAllRelations($documentId);
        if (!$dokument) {
            $io->error("Document with ID {$documentId} not found");
            return Command::FAILURE;
        }

        $io->title("Regenerating Document #{$documentId}");
        $io->text("Current title: " . $dokument->getTytul());
        $io->text("Current type: " . $dokument->getTyp());

        if (!DocumentFactory::isSupported($dokument->getTyp())) {
            $io->error("Document type '{$dokument->getTyp()}' is not supported by DocumentFactory");
            return Command::FAILURE;
        }

        try {
            // Przygotuj dane na podstawie dokumentu
            $data = [];
            if ($dokument->getKandydat()) {
                $data['kandydat'] = $dokument->getKandydat();
            }
            if ($dokument->getCzlonek()) {
                $data['czlonek'] = $dokument->getCzlonek();
            }
            if ($dokument->getDaneDodatkowe()) {
                $data = array_merge($data, $dokument->getDaneDodatkowe());
            }

            // Generuj nową treść
            $newContent = DocumentFactory::generateContent($dokument->getTyp(), $dokument, $data);

            $io->section("Old content preview:");
            $io->text(substr($dokument->getTresc(), -200) . "...");

            $io->section("New content preview:");
            $io->text(substr($newContent, -200) . "...");

            if ($io->confirm('Update document content?', false)) {
                $dokument->setTresc($newContent);
                $this->entityManager->flush();
                $io->success("Document content updated successfully!");
            } else {
                $io->info("Document content not updated");
            }

        } catch (\Exception $e) {
            $io->error("Error regenerating document: " . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}