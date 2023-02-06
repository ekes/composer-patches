<?php

namespace cweagans\Composer\Patcher;

use cweagans\Composer\Patch;
use Composer\IO\IOInterface;

class GitPatcher extends PatcherBase
{
    protected string $tool = 'git';

    public function apply(Patch $patch, string $path): bool
    {
        // If the path isn't a git repo, don't even try.
        // @see https://stackoverflow.com/a/27283285
        if (!is_dir($path . '/.git')) {
            return false;
        }

        // Dry run first.
        $status = $this->executeCommand(
            '%s -C %s apply --check --verbose -p%s %s',
            $this->patchTool(),
            $path,
            $patch->depth,
            $patch->localPath
        );
        if (str_starts_with($this->executor->getErrorOutput(), 'Skipped')) {
            // Git will indicate success but silently skip patches in some scenarios.
            //
            // @see https://github.com/cweagans/composer-patches/pull/165
            $status = false;
        }

        // If the check failed, then don't proceed with actually applying the patch.
        if (!$status) {
            return false;
        }

        // Otherwise, we can try to apply the patch.
        return $this->executeCommand(
            '%s -C %s apply -p%s %s',
            $this->patchTool(),
            $path,
            $patch->depth,
            $patch->localPath
        );
    }

    public function canUse(): bool
    {
        $output = "";
        $result = $this->executor->execute($this->patchTool() . ' --version', $output);
        $usable = ($result === 0);

        $this->io->write(self::class . " usable: " . ($usable ? "yes" : "no"), true, IOInterface::DEBUG);

        return $usable;
    }
}
/*
$patch_levels = $this->getConfig('patch-levels');
foreach ($patch_levels as $patch_level) {
    if ($this->io->isVerbose()) {
        $comment = 'Testing ability to patch with git apply.';
        $comment .= ' This command may produce errors that can be safely ignored.';
        $this->io->write('<comment>' . $comment . '</comment>');
    }
    $checked = $this->executeCommand(
        'git -C %s apply --check -v %s %s',
        $install_path,
        $patch_level,
        $filename
    );
    $output = $this->executor->getErrorOutput();
    if (substr($output, 0, 7) === 'Skipped') {
        // Git will indicate success but silently skip patches in some scenarios.
        //
        // @see https://github.com/cweagans/composer-patches/pull/165
        $checked = false;
    }
    if ($checked) {
        // Apply the first successful style.
        $patched = $this->executeCommand(
            'git -C %s apply %s %s',
            $install_path,
            $patch_level,
            $filename
        );
        break;
    }
        */
