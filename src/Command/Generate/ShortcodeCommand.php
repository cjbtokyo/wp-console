<?php

/**
 * @file
 * Contains \WP\Console\Command\Generate\ShortcodeCommand.
 */

namespace WP\Console\Command\Generate;


use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Command\Command;
use WP\Console\Command\Shared\CommandTrait;
use WP\Console\Command\Shared\PluginTrait;
use WP\Console\Command\Shared\ConfirmationTrait;
use WP\Console\Generator\ShortcodeGenerator;
use WP\Console\Core\Utils\StringConverter;
use WP\Console\Extension\Manager;
use WP\Console\Utils\Site;
use WP\Console\Core\Style\WPStyle;
use WP\Console\Utils\Validator;

class ShortcodeCommand extends Command
{
    use CommandTrait;
    use PluginTrait;
    use ConfirmationTrait;

    /**
     * @var ShortcodeGenerator
     */
    protected $generator;

    /**
     * @var Manager
     */
    protected $extensionManager;

    /**
     * @var Site
     */
    protected $site;

    /**
     * @var Validator
     */
    protected $validator;

    /**
     * @var StringConverter
     */
    protected $stringConverter;

    /**
     * CommandCommand constructor.
     *
     * @param ShortcodeGenerator $generator
     * @param Manager          $extensionManager
     * @param Site          $site
     * @param Validator        $validator
     * @param StringConverter  $stringConverter
     */
    public function __construct(
        ShortcodeGenerator $generator,
        Manager $extensionManager,
        Site $site,
        Validator $validator,
        StringConverter $stringConverter
    ) {
        $this->generator = $generator;
        $this->extensionManager = $extensionManager;
        $this->site = $site;
        $this->validator = $validator;
        $this->stringConverter = $stringConverter;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('generate:shortcode')
            ->setDescription($this->trans('commands.generate.shortcode.description'))
            ->setHelp($this->trans('commands.generate.command.help'))
            ->addOption(
                'plugin',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.common.options.plugin')
            )
            ->addOption(
                'tag',
                '',
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.generate.shortcode.options.tag')
            )
            ->setAliases(['gs']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new WPStyle($input, $output);

        $plugin = $input->getOption('plugin');
        $tag = $input->getOption('tag');
        $yes = $input->hasOption('yes')?$input->getOption('yes'):false;

        // @see use WP\Console\Command\Shared\ConfirmationTrait::confirmGeneration
        if (!$this->confirmGeneration($io, $yes)) {
            return;
        }


        $className = $this->stringConverter->humanToCamelCase($plugin);
        $pluginPath = $this->extensionManager->getPlugin($plugin)->getPath();
        $pluginFile = $this->extensionManager->getPlugin($plugin)->getPathname();
        $pluginNameSpace = $this->stringConverter->humanToCamelCase($plugin);
        $pluginCamelCaseMachineName = $this->stringConverter->createMachineName($this->stringConverter->humanToCamelCase($plugin));


        $this->generator->generate(
            $tag,
            $plugin,
            $pluginPath,
            $pluginNameSpace,
            $pluginCamelCaseMachineName,
            $className,
            $pluginFile
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new WPStyle($input, $output);
        $shortcodes = $this->site->getShortCodes();

        // --plugin
        $plugin = $input->getOption('plugin');
        if (!$plugin) {
            $plugin = $this->pluginQuestion($io);
            $input->setOption('plugin', $plugin);
        }
        // --tag
        $tag = $input->getOption('tag');
        if (!$tag) {
            $tag = $io->ask(
                $this->trans('commands.generate.shortcode.questions.tag'),
                '',
                function ($tag) use($shortcodes, $io) {
                    if(empty($tag)) {
                        throw new \InvalidArgumentException(
                            sprintf(
                                $this->trans('commands.generate.shortcode.warnings.tag-required'),
                                $tag
                            )
                        );
                    } elseif(array_key_exists($tag, $shortcodes)) {
                        throw new \InvalidArgumentException(
                            sprintf(
                                $this->trans('commands.generate.shortcode.warnings.tag-unavailable'),
                                $tag
                            )
                        );
                    }

                    $tagMachineNameCamelCase = $this->stringConverter->createMachineName($this->stringConverter->humanToCamelCase($tag));

                    if($tag != $tagMachineNameCamelCase) {
                        $io->warning(
                           sprintf(
                            $this->trans('commands.generate.shortcode.warnings.tag-transformed'),
                            $tag,
                            $tagMachineNameCamelCase
                           )
                        );
                    }
                    return $tagMachineNameCamelCase;
                }
            );
            $input->setOption('tag', $tag);
        }

    }
}
