<?php

namespace DownloadFilesRegistrations;

use MapasCulturais\App;

class Plugin extends \MapasCulturais\Plugin
{
    function __construct($config = [])
    {
        $config += [];

        parent::__construct($config);
    }


    public function _init()
    {
        $app = App::i();

        $self = $this;

        $app->hook("component(mc-export-spreadsheet):after", function () {
            $entity = $this->controller->requestedEntity;
            $this->part('button-download-files-registrations', ['entity' => $entity]);
        });

        $app->hook("template(registration.view.single-tab):begin", function () {
            $entity = $this->controller->requestedEntity;
            $this->part('button-download-registration', ['entity' => $entity ]);
        });

        $app->hook('GET(opportunity.registrationsDownload)', function () use ($self, $app) {
            ini_set('max_execution_time', '0');
            /** @var ControllersOpportunity $this */
            $this->requireAuthentication();
            $opportunity = $app->repo('Opportunity')->find($this->data['entity']);
            

            if(!$opportunity) {
                $app->pass();
            }

            $repository = $app->repo('Registration');
            $queryBuilder = $repository->createQueryBuilder('r');

            if (isset($this->data['registrationId'])) {
                $registrationId = $this->data['registrationId'];
                $queryBuilder
                    ->select('r.id') 
                    ->where('r.opportunity = :opportunity')
                    ->andWhere('r.id = :id')
                    ->setParameter('opportunity', $opportunity->id)
                    ->setParameter('id', $registrationId);

                    $zipFileName = "opportunity-$opportunity->id-registration-$registrationId-files.zip";
            } else {
                $queryBuilder
                    ->select('r.id') 
                    ->where('r.opportunity = :opportunity')
                    ->setParameter('opportunity', $opportunity->id);

                    $zipFileName = "opportunity-$opportunity->id-registrations-files.zip";
            }

            $result = $queryBuilder->getQuery()->getResult();

            $ids = array_column($result, 'id');
            $registrations = $ids;

            $opportunity->checkPermission('@control');

            $zip = new \ZipArchive();

            $baseDir = PRIVATE_FILES_PATH . 'registration';

            if ($zip->open($zipFileName, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                die('Não foi possível criar o arquivo ZIP.');
            }

            foreach ($registrations as $id) {
                $dirPath = $baseDir . DIRECTORY_SEPARATOR . $id;
                if (is_dir($dirPath)) {
                    $self->addFilesToZip($dirPath, $zip);
                }
            }

            $zip->close();

            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . basename($zipFileName) . '"');
            header('Content-Length: ' . filesize($zipFileName));

            readfile($zipFileName);

            unlink($zipFileName);
        });

    }

    function addFilesToZip($dir, $zip, $relativePath = '') {
        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            
            $filePath = $dir . DIRECTORY_SEPARATOR . $file;
            $zipPath = $relativePath . DIRECTORY_SEPARATOR . $file;
            
            // Verifica se o arquivo é um diretório
            if (is_dir($filePath)) {
                $this->addFilesToZip($filePath, $zip, $zipPath);
            } else {
                // Adiciona apenas arquivos com extensão .zip
                if (pathinfo($filePath, PATHINFO_EXTENSION) === 'zip') {
                    $zip->addFile($filePath, ltrim($zipPath, DIRECTORY_SEPARATOR));
                }
            }
        }
    }

    public function register(){}
}
