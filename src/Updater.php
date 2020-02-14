<?php /** @noinspection PhpUnused */

namespace CodexSoft\ComposerLocalPackageUpdater;

use Symfony\Component\Filesystem\Filesystem;

class Updater
{
    const COMPOSER_JSON_LOCAL = 'composer.local.json';
    const COMPOSER_LOCK_LOCAL = 'composer.local.lock';
    const COMPOSER_JSON = 'composer.json';
    const COMPOSER_LOCK = 'composer.lock';

    /** @var Filesystem */
    private $fs;

    /** @var array */
    private $packagesToOverride;

    /** @var string */
    private $composerCommand = 'composer';

    private $composerOptions = '--no-scripts';

    /** @var array  */
    private $mergeConfig = [];

    private function output($msg)
    {
        echo "\n$msg";
    }

    public function add(string $packageName, string $branchName, string $repoPath): self
    {
        $this->packagesToOverride[] = [
            'packageName' => $packageName,
            'repoPath' => $repoPath,
            'branchName' => $branchName,
        ];
        return $this;
    }

    public function __construct()
    {
        $this->fs = new Filesystem();
    }

    /**
     * @param $condition
     * @param $messageOnFail
     *
     * @throws \RuntimeException
     */
    private function assert($condition, $messageOnFail)
    {
        if (!$condition) {
            throw new \RuntimeException($messageOnFail);
        }
    }

    /**
     * @param bool $fast
     *
     * @throws \RuntimeException
     */
    public function run(bool $fast = false)
    {
        $fs = $this->fs;
        $packagesToOverride = $this->packagesToOverride;

        $packageNames = [];
        foreach ($packagesToOverride as $packageToOverride) {
            $packageNames[] = $packageToOverride['packageName'];
        }

        if ($fast) {
            $this->assert($fs->exists(self::COMPOSER_JSON_LOCAL), self::COMPOSER_JSON_LOCAL.' does not exists');
            $this->assert($fs->exists(self::COMPOSER_LOCK_LOCAL), self::COMPOSER_LOCK_LOCAL.' does not exists');
        } else {
            $this->executeCommand("{$this->composerCommand} install", 'Installing original composer packages...');
            $this->assert($fs->exists(self::COMPOSER_JSON), self::COMPOSER_JSON.' does not exists');
            $this->assert($fs->exists(self::COMPOSER_LOCK), self::COMPOSER_LOCK.' does not exists');
            $fs->copy(self::COMPOSER_JSON, self::COMPOSER_JSON_LOCAL);
            $fs->copy(self::COMPOSER_LOCK, self::COMPOSER_LOCK_LOCAL);

            $composerEncoded = \file_get_contents(self::COMPOSER_JSON);
            try {
                $composerDecoded = \json_decode($composerEncoded, true);
            } catch (\Exception $e) {
                $this->removeGeneratedFiles();
                throw new \RuntimeException('Failed to decode '.self::COMPOSER_JSON.': '.$e->getMessage(), 0, $e);
            }

            if ($this->mergeConfig) {
                $composerDecoded = \array_merge($composerDecoded, $this->mergeConfig);
            }

            foreach ($packagesToOverride as $packageToOverride) {
                $composerDecoded['repositories'][] = [
                    'type' => 'path',
                    'url' => $packageToOverride['repoPath'],
                    'options' => [
                        'symlink' => false,
                    ],
                ];

                $composerDecoded['require'][$packageToOverride['packageName']] = $packageToOverride['branchName'];
            }

            try {
                $composerEncoded = json_encode($composerDecoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n";
            } catch (\Exception $e) {
                $this->removeGeneratedFiles();
                throw new \RuntimeException('Failed to encode '.self::COMPOSER_JSON_LOCAL.': '.$e->getMessage(), 0, $e);
            }

            $fs->dumpFile(self::COMPOSER_JSON_LOCAL,$composerEncoded);
        }

        $this->executeCommand('COMPOSER='.self::COMPOSER_JSON_LOCAL." {$this->composerCommand} update ".\implode(' ', $packageNames)." {$this->composerOptions}", 'Updating packages from local branches...');
        $this->output('Done!');
    }

    private function executeCommand($cmd, $title)
    {
        $this->output($title);
        $this->output($cmd);
        \exec($cmd, $null);
    }

    private function removeGeneratedFiles()
    {
        if ($this->fs->exists(self::COMPOSER_JSON_LOCAL)) {
            $this->fs->remove(self::COMPOSER_JSON_LOCAL);
        }

        if ($this->fs->exists(self::COMPOSER_LOCK_LOCAL)) {
            $this->fs->remove(self::COMPOSER_LOCK_LOCAL);
        }
    }

    /**
     * @param string $composerCommand
     *
     * @return Updater
     */
    public function setComposerCommand(string $composerCommand): Updater
    {
        $this->composerCommand = $composerCommand;
        return $this;
    }

    /**
     * @param string $composerOptions
     *
     * @return Updater
     */
    public function setComposerOptions(string $composerOptions): Updater
    {
        $this->composerOptions = $composerOptions;
        return $this;
    }

    /**
     * @param array $mergeConfig
     *
     * @return Updater
     */
    public function setMergeConfig(array $mergeConfig): Updater
    {
        $this->mergeConfig = $mergeConfig;
        return $this;
    }

}

