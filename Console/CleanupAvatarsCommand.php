<?php

declare(strict_types=1);

namespace Kanboard\Plugin\BlobStorage\Console;

use Kanboard\Console\BaseCommand;
use Kanboard\Model\UserModel;
use Kanboard\Plugin\BlobStorage\BlobObjectStorage;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatter;

class CleanupAvatarsCommand extends BaseCommand
{
    protected function configure(): void
    {
        $this->setName('blob:cleanup-avatars')
            ->setDescription('Delete orphaned avatar blobs and clear missing avatar paths (dry-run by default)')
            ->addOption('apply', null, InputOption::VALUE_NONE, 'Apply the cleanup');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $apply = (bool) $input->getOption('apply');
        $output->writeln($apply ? 'Applying avatar cleanup.' : 'Dry-run: no changes will be made.');

        try {
            $storage = $this->container['objectStorage'];
            if (!$storage instanceof BlobObjectStorage) {
                throw new \RuntimeException('Blob object storage is not enabled.');
            }

            // Finish the listing successfully before making any changes.
            $files = $storage->getAvatarFiles();
            $users = $this->db->table(UserModel::TABLE)->columns('id', 'avatar_path')->findAll();
            $referenced = array_fill_keys(array_column($users, 'avatar_path'), true);
            $existingPrefixes = [];
            $deleted = 0;
            $cleared = 0;

            foreach ($files as $file) {
                $prefix = $file->getPrefix();
                $existingPrefixes[$prefix] = true;
                if (isset($referenced[$prefix])) {
                    continue;
                }

                if ($apply) {
                    if ($this->db->table(UserModel::TABLE)->eq('avatar_path', $prefix)->exists()) {
                        continue;
                    }
                    $storage->removeFileByIdentifier($file->getIdentifier());
                }
                $output->writeln(($apply ? 'Deleted blob: ' : 'Would delete blob: ').
                    OutputFormatter::escape($prefix.' ('.$file->getIdentifier().')'));
                $deleted++;
            }

            foreach ($users as $user) {
                $path = $user['avatar_path'] ?? '';
                if (!str_starts_with($path, 'avatars/') || isset($existingPrefixes[$path])) {
                    continue;
                }

                if ($apply) {
                    // Recheck Blob and only update the path we originally inspected.
                    if ($storage->hasFile($path)) {
                        continue;
                    }
                    if (!$this->db->table(UserModel::TABLE)->eq('id', $user['id'])
                        ->eq('avatar_path', $path)->update(['avatar_path' => null])) {
                        throw new \RuntimeException('Failed to clear avatar path for user '.$user['id']);
                    }
                }
                $output->writeln(($apply ? 'Cleared path: ' : 'Would clear path: ').
                    OutputFormatter::escape('user '.$user['id'].' — '.$path));
                $cleared++;
            }

            $output->writeln(sprintf('%d orphaned blobs, %d broken avatar paths.', $deleted, $cleared));
            return 0;
        } catch (\Throwable $exception) {
            $output->writeln('<error>Cleanup failed: '.OutputFormatter::escape($exception->getMessage()).'</error>');
            return 1;
        }
    }
}
