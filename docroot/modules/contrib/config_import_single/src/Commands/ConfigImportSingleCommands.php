<?php

namespace Drupal\config_import_single\Commands;

use Drupal\config\StorageReplaceDataWrapper;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigImporter;
use Drupal\Core\Config\ConfigImporterException;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Config\Importer\ConfigImporterBatch;
use Drupal\Core\Config\StorageComparer;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drush\Commands\DrushCommands;
use Symfony\Component\Yaml\Parser;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Webmozart\PathUtil\Path;

/**
 * Class to import single files into config.
 *
 * @package Drupal\config_import_single\Commands
 */
class ConfigImportSingleCommands extends DrushCommands {

  /**
   * CachedStorage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  private $storage;

  /**
   * Event dispatcher.
   *
   * @var \Symfony\Contracts\EventDispatcher\EventDispatcherInterface
   */
  private $eventDispatcher;

  /**
   * Config manager.
   *
   * @var \Drupal\Core\Config\ConfigManagerInterface
   */
  private $configManager;

  /**
   * Lock.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  private $lock;

  /**
   * Config typed.
   *
   * @var \Drupal\Core\Config\TypedConfigManagerInterface
   */
  private $configTyped;

  /**
   * ModuleHandler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  private $moduleHandler;

  /**
   * Module installer.
   *
   * @var \Drupal\Core\Extension\ModuleInstallerInterface
   */
  private $moduleInstaller;

  /**
   * Theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  private $themeHandler;

  /**
   * String translation.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  private $stringTranslation;

  /**
   * Extension list module.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  private $extensionListModule;

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $configFactory;

  /**
   * ConfigImportSingleCommands constructor.
   *
   * @param \Drupal\Core\Config\StorageInterface $storage
   *   Storage.
   * @param \Symfony\Contracts\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   Event Dispatcher.
   * @param \Drupal\Core\Config\ConfigManagerInterface $configManager
   *   Config Manager.
   * @param \Drupal\Core\Lock\LockBackendInterface $lock
   *   Lock.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $configTyped
   *   Config typed.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   Module handler.
   * @param \Drupal\Core\Extension\ModuleInstallerInterface $moduleInstaller
   *   Module Installer.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $themeHandler
   *   Theme handler.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $stringTranslation
   *   String Translation.
   * @param \Drupal\Core\Extension\ModuleExtensionList $extensionListModule
   *   Extension list module.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config factory.
   */
  public function __construct(StorageInterface $storage, EventDispatcherInterface $eventDispatcher, ConfigManagerInterface $configManager, LockBackendInterface $lock, TypedConfigManagerInterface $configTyped, ModuleHandlerInterface $moduleHandler, ModuleInstallerInterface $moduleInstaller, ThemeHandlerInterface $themeHandler, TranslationInterface $stringTranslation, ModuleExtensionList $extensionListModule, ConfigFactoryInterface $configFactory) {
    parent::__construct();
    $this->storage = $storage;
    $this->eventDispatcher = $eventDispatcher;
    $this->configManager = $configManager;
    $this->lock = $lock;
    $this->configTyped = $configTyped;
    $this->moduleHandler = $moduleHandler;
    $this->moduleInstaller = $moduleInstaller;
    $this->themeHandler = $themeHandler;
    $this->stringTranslation = $stringTranslation;
    $this->extensionListModule = $extensionListModule;
    $this->configFactory = $configFactory;
  }

  /**
   * Import a single configuration file.
   *
   * (copied from drupal console, which isn't D9 ready yet)
   *
   * @param string $file
   *   The path to the file to import.
   *
   * @command config_import_single:single-import
   *
   * @usage config_import_single:single-import <file>
   *
   * @validate-module-enabled config_import_single
   *
   * @aliases cis
   *
   * @throws \Exception
   */
  public function singleImport(string $file) {
    if (!$file) {
      throw new \Exception("No file specified.");
    }

    if (!file_exists($file)) {
      throw new \Exception("File not found.");
    }

    $source_storage = new StorageReplaceDataWrapper(
      $this->storage
    );

    $name = Path::getFilenameWithoutExtension($file);
    $ymlFile = new Parser();
    $value = $ymlFile->parse(file_get_contents($file));
    $source_storage->replaceData($name, $value);

    $storageComparer = new StorageComparer(
      $source_storage,
      $this->storage
    );

    if ($this->configImport($storageComparer)) {
      $this->output()->writeln("Successfully imported $name");
    }
    else {
      throw new \Exception("Failed importing file");
    }
  }

  /**
   * Import the config.
   *
   * @param \Drupal\Core\Config\StorageComparer $storageComparer
   *   The storage comparer.
   *
   * @return bool|void
   *   Returns TRUE if succeeded.
   */
  private function configImport(StorageComparer $storageComparer) {
    $configImporter = new ConfigImporter(
      $storageComparer,
      $this->eventDispatcher,
      $this->configManager,
      $this->lock,
      $this->configTyped,
      $this->moduleHandler,
      $this->moduleInstaller,
      $this->themeHandler,
      $this->stringTranslation,
      $this->extensionListModule
    );

    if ($configImporter->alreadyImporting()) {
      $this->output()->writeln('Import already running.');
    }
    else {
      if ($configImporter->validate()) {
        try {
          $syncSteps = $configImporter->initialize();
          $batch = [
            'operations' => [],
            'finished' => [ConfigImporterBatch::class, 'finish'],
            'title' => $this->stringTranslation->translate('Importing configuration'),
            'init_message' => $this->stringTranslation->translate('Starting configuration import.'),
            'progress_message' => $this->stringTranslation->translate('Completed @current step of @total.'),
            'error_message' => $this->stringTranslation->translate('Configuration import has encountered an error.'),
          ];
          foreach ($syncSteps as $syncStep) {
            $batch['operations'][] = [
              [ConfigImporterBatch::class, 'process'],
              [$configImporter, $syncStep],
            ];
          }

          batch_set($batch);
          drush_backend_batch_process();

          $this->configFactory->reset();
          return TRUE;
        }
        catch (ConfigImporterException $e) {
          return FALSE;
        }
      }
    }
  }

}
