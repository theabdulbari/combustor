<?php namespace Combustor;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class InstallFactoryCommand extends Command
{

	/**
	 * Set the configurations of the specified command
	 */
	protected function configure()
	{
		$this->setName('install:factory')
			->setDescription('Install the customized factory pattern');
	}

	/**
	 * Execute the command
	 * 
	 * @param  InputInterface  $input
	 * @param  OutputInterface $output
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		/**
		 * ---------------------------------------------------------------------------------------------
		 * Adding the Factory.php to the "libraries" directory
		 * ---------------------------------------------------------------------------------------------
		 */

		if (file_exists(APPPATH . 'libraries/Factory.php')) {
			exit($output->writeln('<error>The customized factory pattern is already installed!</error>'));
		}

		$autoload = file_get_contents(APPPATH . 'config/autoload.php');

		preg_match_all('/\$autoload\[\'libraries\'\] = array\((.*?)\)/', $autoload, $match);

		$libraries = explode(', ', end($match[1]));

		if ( ! in_array('\'factory\'', $libraries)) {
			array_push($libraries, '\'factory\'');

			$autoload = preg_replace(
				'/\$autoload\[\'libraries\'\] = array\([^)]*\);/',
				'$autoload[\'libraries\'] = array(' . implode(', ', $libraries) . ');',
				$autoload
			);

			$file = fopen(APPPATH . 'config/autoload.php', 'wb');

			file_put_contents(APPPATH . 'config/autoload.php', $autoload);
			fclose($file);
		}

		$factory = file_get_contents(__DIR__ . '/Templates/Factory.txt');
		$file    = fopen(APPPATH . 'libraries/Factory.php', 'wb');

		file_put_contents(APPPATH . 'libraries/Factory.php', $factory);
		fclose($file);

		$combustor = file_get_contents(VENDOR . 'rougin/combustor/bin/combustor');

		if (strpos($combustor, '// $application->add(new Combustor\CreateControllerCommand);') !== FALSE) {
			$search = array(
'// $application->add(new Combustor\CreateControllerCommand);
// $application->add(new Combustor\CreateLayoutCommand);
// $application->add(new Combustor\CreateModelCommand);
// $application->add(new Combustor\CreateScaffoldCommand);
// $application->add(new Combustor\CreateViewCommand);',
				'// $application->add(new Combustor\RemoveFactoryCommand);'
			);
			$replace = array(
'$application->add(new Combustor\CreateControllerCommand);
$application->add(new Combustor\CreateLayoutCommand);
$application->add(new Combustor\CreateModelCommand);
$application->add(new Combustor\CreateScaffoldCommand);
$application->add(new Combustor\CreateViewCommand);',
				'$application->add(new Combustor\RemoveFactoryCommand);'
			);

			$combustor = str_replace($search, $replace, $combustor);

			$file = fopen(VENDOR . 'rougin/combustor/bin/combustor', 'wb');

			file_put_contents(VENDOR . 'rougin/combustor/bin/combustor', $combustor);
			fclose($file);
		}

		$output->writeln('<info>The customized factory pattern is now installed successfully!</info>');
	}

}