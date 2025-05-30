<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Cycle\Command\Migration;

use Cycle\Migrations\Exception\RepositoryException;
use Cycle\Migrations\MigrationInterface;
use Cycle\Migrations\State;
use Cycle\Schema\Generator\Migrations\MigrationImage;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Yii\Cycle\Command\CycleDependencyProxy;

abstract class BaseMigrationCommand extends Command
{
    /**
     * @psalm-suppress MissingClassConstType
     */
    protected const MIGRATION_STATUS = [
        State::STATUS_UNDEFINED => 'undefined',
        State::STATUS_PENDING => 'pending',
        State::STATUS_EXECUTED => 'executed',
    ];

    public function __construct(protected CycleDependencyProxy $promise)
    {
        parent::__construct();
    }

    protected function createEmptyMigration(
        OutputInterface $output,
        string $name,
        ?string $database = null
    ): ?MigrationImage {
        if ($database === null) {
            // get default database
            $database = $this->promise->getDatabaseProvider()->database()->getName();
        }
        $migrator = $this->promise->getMigrator();

        $migrationSkeleton = new MigrationImage($this->promise->getMigrationConfig(), $database);
        $migrationSkeleton->setName($name);

        $className = $migrationSkeleton->getClass()->getName();
        \assert($className !== null);

        try {
            $migrationFile = $migrator->getRepository()->registerMigration(
                $migrationSkeleton->buildFileName(),
                $className,
                $migrationSkeleton->getFile()->render()
            );
        } catch (RepositoryException $e) {
            $output->writeln('<fg=yellow>Can not create migration</>');
            /**
             * @infection-ignore-all
             * ConcatOperandRemoval
             * Removing closing tag works (probably it's automatically fixed by formatter), but makes no sense.
             */
            $output->writeln('<fg=red>' . $e->getMessage() . '</>');
            return null;
        }
        $output->writeln('<info>New migration file has been created</info>');
        $output->writeln("<fg=cyan>$migrationFile</>");
        return $migrationSkeleton;
    }

    /**
     * @return MigrationInterface[]
     */
    protected function findMigrations(OutputInterface $output): array
    {
        $list = $this->promise->getMigrator()->getMigrations();
        $output->writeln(
            sprintf(
                '<info>Total %d migration(s) found in %s</info>',
                count($list),
                $this->promise->getMigrationConfig()->getDirectory()
            )
        );
        return $list;
    }
}
