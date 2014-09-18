<?php

namespace Combustor;

use Combustor\Tools\Inflect;
use Combustor\Tools\GetColumns;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CreateControllerCommand extends Command
{

	/**
	 * Set the configurations of the specified command
	 */
	protected function configure()
	{
		$this->setName('create:controller')
			->setDescription('Create a new controller')
			->addArgument(
				'name',
				InputArgument::REQUIRED,
				'Name of the controller'
			)->addOption(
				'keep',
				null,
				InputOption::VALUE_NONE,
				'Keeps the name to be used'
			)->addOption(
				'snake',
				NULL,
				InputOption::VALUE_NONE,
				'Use the snake case naming convention for the accessor and mutators'
			);
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
		 * Get the controller template
		 */
		
		$controller = file_get_contents(__DIR__ . '/Templates/Controller.txt');
		
		/**
		 * Get the columns from the specified name
		 */

		$columns = new GetColumns($input->getArgument('name'), $output);

		$models = '\'$singular\'';

		$columnsCreate = NULL;
		$columnsEdit = NULL;
		$columnsValidate = NULL;
		$counter = 0;

		foreach ($columns->result() as $row) {
			$methodName = 'set_' . strtolower($row->Field);
			$methodName = ($input->getOption('snake')) ? Inflect::underscore($methodName) : Inflect::camelize($methodName);

			if ($counter != 0) {
				if ($row->Field != 'datetime_updated') {
					$columnsCreate .= "			";
				}

				if ($row->Field != 'datetime_created') {
					$columnsEdit .= "			";
				}

				if ($row->Field != 'password' && $row->Field != 'datetime_created' && $row->Field != 'datetime_updated') {
					$columnsValidate .= "			";
				}
			}

			if ($row->Extra == 'auto_increment') {
				continue;
			} elseif ($row->Key == 'MUL') {
				$entity = str_replace('_id', '', $row->Field);
				$models .= ",\n" . '			\'' . $entity . '\'';

				$columnsCreate .= "\n" . '			$' . $entity . ' = $this->doctrine->em->find(\'' . $entity . '\', $this->input->post(\'' . $row->Field . '\'));' . "\n";
				$columnsCreate .= '			$this->$singular->' . $methodName . '($' . $entity . ');' . "\n\n";

				$columnsEdit .= "\n" . '			$' . $entity . ' = $this->doctrine->em->find(\'' . $entity . '\', $this->input->post(\'' . $row->Field . '\'));' . "\n";
				$columnsEdit .= '			$$singular->' . $methodName . '($' . $entity . ');' . "\n\n";
			} elseif ($row->Field == 'password') {
				$columnsCreate .= "\n" . file_get_contents(__DIR__ . '/Templates/Miscellaneous/CheckCreatePassword.txt') . "\n\n";
				$columnsEdit .= "\n" . file_get_contents(__DIR__ . '/Templates/Miscellaneous/CheckEditPassword.txt') . "\n\n";
			} else {
				$column = ($row->Field == 'datetime_created' || $row->Field == 'datetime_updated') ? 'now' : $row->Field;

				if ($row->Field != 'datetime_updated') {
					$columnsCreate .= '$this->$singular->' . $methodName . '($this->input->post(\'' . $column . '\'));' . "\n";
				}

				if ($row->Field != 'datetime_created') {
					$columnsEdit .= '$$singular->' . $methodName . '($this->input->post(\'' . $column . '\'));' . "\n";
				}
			}

			if ($row->Field != 'password' && $row->Field != 'datetime_created' && $row->Field != 'datetime_updated') {
				$columnsValidate .= '\'' . $row->Field . '\' => \'' . str_replace('_', ' ', $row->Field) . '\',' . "\n";
			}

			$counter++;
		}

		/**
		 * Search and replace the following keywords from the template
		 */

		$search = array(
			'$models',
			'$columnsCreate',
			'$columnsEdit',
			'$columnsValidate',
			'$controller',
			'$plural',
			'$singular'
		);

		$replace = array(
			rtrim($models),
			rtrim($columnsCreate),
			rtrim($columnsEdit),
			substr($columnsValidate, 0, -2),
			ucfirst(Inflect::pluralize($input->getArgument('name'))),
			Inflect::pluralize($input->getArgument('name')),
			Inflect::singularize($input->getArgument('name'))
		);

		$controller = str_replace($search, $replace, $controller);

		/**
		 * Create a new file and insert the generated template
		 */

		$name = ($input->getOption('keep')) ? $input->getArgument('name') : Inflect::pluralize($input->getArgument('name'));

		$filename = APPPATH . 'controllers/' . ucfirst($name) . '.php';

		if (file_exists($filename)) {
			$output->writeln('<error>The ' . Inflect::pluralize($input->getArgument('name')) . ' controller already exists!</error>');

			exit();
		}

		$file = fopen($filename, 'wb');
		file_put_contents($filename, $controller);

		$output->writeln('<info>The controller "' . Inflect::pluralize($input->getArgument('name')) . '" has been created successfully!</info>');
	}
	
}