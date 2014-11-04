<?php
/**
 * Created by Miroslav Čillík <miro@keboola.com>
 * Date: 03/11/14
 * Time: 18:02
 */

namespace Syrup\ComponentBundle\Command;


use Sensio\Bundle\GeneratorBundle\Command\GeneratorCommand;
use Sensio\Bundle\GeneratorBundle\Command\Validators;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Syrup\ComponentBundle\Generator\ComponentGenerator;

class GenerateComponentCommand extends GeneratorCommand
{
	/**
	 * @see Command
	 */
	protected function configure()
	{
		$this
			->setName('syrup:generate:component')
			->setDefinition(array(
				new InputOption('namespace', '', InputOption::VALUE_REQUIRED, 'The namespace of the bundle to create'),
				new InputOption('short-name', '', InputOption::VALUE_REQUIRED, 'Short name of the component (ie. ex-twitter, ex-google-drive, wr-db, ...)'),
				new InputOption('dir', '', InputOption::VALUE_REQUIRED, 'The directory where to create the bundle'),
//				new InputOption('bundle-name', '', InputOption::VALUE_REQUIRED, 'The optional bundle name'),
//				new InputOption('format', '', InputOption::VALUE_REQUIRED, 'Use the format for configuration files (php, xml, yml, or annotation)'),
//				new InputOption('structure', '', InputOption::VALUE_NONE, 'Whether to generate the whole directory structure'),
			))
			->setDescription('Generates a component')
			->setHelp(<<<EOT
The <info>generate:bundle</info> command helps you generate new components.

By default, the command interacts with the developer to tweak the generation.
Any passed option will be used as a default value for the interaction
(<comment>--namespace</comment> is the only one needed if you follow the
conventions):

<info>php app/console generate:bundle --namespace=Acme/BlogBundle</info>

Note that you can use <comment>/</comment> instead of <comment>\\ </comment>for the namespace delimiter to avoid any
problem.

If you want to disable any user interaction, use <comment>--no-interaction</comment> but don't forget to pass all needed options:

<info>php app/console generate:bundle --namespace=Acme/BlogBundle --dir=src [--bundle-name=...] --no-interaction</info>

Note that the bundle namespace must end with "Bundle".
EOT
			)
		;
	}

	/**
	 * @see Command
	 *
	 * @throws \InvalidArgumentException When namespace doesn't end with Bundle
	 * @throws \RuntimeException         When bundle can't be executed
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$dialog = $this->getDialogHelper();

		if ($input->isInteractive()) {
			if (!$dialog->askConfirmation($output, $dialog->getQuestion('Do you confirm generation', 'yes', '?'), true)) {
				$output->writeln('<error>Command aborted</error>');

				return 1;
			}
		}

		foreach (array('namespace', 'short-name') as $option) {
			if (null === $input->getOption($option)) {
				throw new \RuntimeException(sprintf('The "%s" option must be provided.', $option));
			}
		}

		$namespace = Validators::validateBundleNamespace($input->getOption('namespace'));
		$bundle = strtr($namespace, array('\\' => ''));
		$bundle = Validators::validateBundleName($bundle);

		$dir = realpath(__DIR__ . '/../../../../../../');
		if (null !== $input->getOption('dir')) {
			$dir = $input->getOption('dir');
		}

		$dir = Validators::validateTargetDir($dir, $bundle, $namespace);

		if (!$this->getContainer()->get('filesystem')->isAbsolutePath($dir)) {
			$dir = getcwd().'/'.$dir;
		}

		$format = 'yml';
		$format = Validators::validateFormat($format);
		$structure = false;

		$dialog->writeSection($output, 'Bundle generation');

		/** @var ComponentGenerator $generator */
		$generator = $this->getGenerator();
		$generator->generate($namespace, $bundle, $dir, $format);

		$output->writeln('Generating the bundle code: <info>OK</info>');

		$errors = array();
		$runner = $dialog->getRunner($output, $errors);

		// check that the namespace is already autoloaded
//		$runner($this->checkAutoloader($output, $namespace, $bundle));

		// register the bundle in the Kernel class
//		$runner($this->updateKernel($dialog, $input, $output, $this->getContainer()->get('kernel'), $namespace, $bundle));

		// routing
//		$runner($this->updateRouting($dialog, $input, $output, $bundle, $format));

		//@todo: update parameters.yml file

		$dialog->writeGeneratorSummary($output, $errors);
	}

	protected function createGenerator()
	{
		return new ComponentGenerator($this->getContainer()->get('filesystem'));
	}

	protected function checkAutoloader(OutputInterface $output, $namespace, $bundle)
	{
		$output->write('Checking that the bundle is autoloaded: ');
		if (!class_exists($namespace.'\\'.$bundle)) {
			return array(
				'- Edit the <comment>composer.json</comment> file and register the bundle',
				'  namespace in the "autoload" section:',
				'',
			);
		}
	}
}
