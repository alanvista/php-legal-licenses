<?php

namespace Comcast\PhpLegalLicenses\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateCommand extends DependencyLicenseCommand
{
    /**
     * @var bool
     */
    private $hideVersion = false;
    private $toCsv = false;

    /**
     * Configure the command options.
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('generate')
            ->setDescription('Generate Licenses file from project dependencies.')
            ->addOption('hide-version', 'hv', InputOption::VALUE_NONE, 'Hide dependency version')
            ->addOption('csv', null, InputOption::VALUE_NONE, 'Output csv format');
    }

    /**
     * Execute the command.
     *
     * @param \Symfony\Component\Console\Input\InputInterface   $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->hideVersion = $input->getOption('hide-version');
        $this->toCsv = $input->getOption('csv');
        $dependencies = $this->getDependencyList();

        $output->writeln('<info>Generating Licenses file...</info>');
        if ($this->toCsv) {

            $this->generateLicensesCSV($dependencies);
        } else {

            $this->generateLicensesText($dependencies);
        }
        $output->writeln('<info>Done!</info>');

        return 0;
    }

    /**
     * Generates Licenses Text using packages retrieved from composer.lock file.
     *
     * @param array $dependencies
     *
     * @return void
     */
    protected function generateLicensesText($dependencies)
    {
        $text = $this->getBoilerplate();

        foreach ($dependencies as $dependency) {
            $text .= $this->getTextForDependency($dependency);
        }

        file_put_contents('licenses.md', $text);
    }

    protected function generateLicensesCSV($dependencies)
    {
        $fp = fopen('licenses.csv', 'w');
        $title = ['name', 'version', 'source', 'license description'];

        fputcsv($fp, $title);
        foreach ($dependencies as $dependency) {
            $dependencyLists = [
                $dependency['name'],
                $this->hideVersion ? '' : $dependency['version'],
                $dependency['source']['url'],
                $this->getTextForDependency($dependency),
            ];
            fputcsv($fp, $dependencyLists);
        }
        fclose($fp);
    }

    /**
     * Returns Boilerplate text for the Licences File.
     *
     * @return string
     */
    protected function getBoilerplate()
    {
        return '# Project Licenses
This file was generated by the [PHP Legal Licenses](https://github.com/Comcast/php-legal-licenses) utility. It contains the name, version and commit sha, description, homepage, and license information for every dependency in this project.

## Dependencies

';
    }

    /**
     * Retrieves text containing version, sha, and license information for the specified dependency.
     *
     * @param array $dependency
     *
     * @return string
     */
    protected function getTextForDependency($dependency)
    {
        $name = $dependency['name'];
        $description = isset($dependency['description']) ? $dependency['description'] : 'Not configured.';
        $version = $dependency['version'];
        $homepage = isset($dependency['homepage']) ? $dependency['homepage'] : 'Not configured.';
        $sha = isset($dependency['source']) ? str_split($dependency['source']['reference'], 7)[0] : 'no sha';
        $licenseNames = isset($dependency['license']) ? implode(', ', $dependency['license']) : 'Not configured.';
        $license = $this->getFullLicenseText($name);

        return $this->generateDependencyText($name, $description, $version, $homepage, $sha, $licenseNames, $license);
    }

    /** Retrieves full license text for a dependency from the vendor directory.
     *
     * @param string $name
     *
     * @return string
     */
    protected function getFullLicenseText($name)
    {
        $path = getcwd()."/vendor/$name/";
        $filenames = ['LICENSE.txt', 'LICENSE.md', 'LICENSE', 'license.txt', 'license.md', 'license', 'LICENSE-2.0.txt'];

        foreach ($filenames as $filename) {
            $text = @file_get_contents($path.$filename);
            if ($text) {
                return '';
            }
        }

        return '';
    }

    /**
     * Generates Dependency Text based on boilerplate.
     *
     * @param string $name
     * @param string $description
     * @param string $version
     * @param string $homepage
     * @param string $sha
     * @param string $licenseNames
     * @param string $license
     *
     * @return string
     */
    protected function generateDependencyText($name, $description, $version, $homepage, $sha, $licenseNames, $license)
    {
        return "$licenseNames";
    }
}
