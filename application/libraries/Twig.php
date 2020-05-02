<?php

class Twig
{
    private $settings = [
        'autoescape' => ''
    ];

    private $twig;

    public function __construct()
    {
        $this->twig = new \Twig\Environment(
            new \Twig\Loader\FilesystemLoader(VIEWPATH), $this->settings);

        $this->load_function();
    }

    /**
     * @param string $template
     * @param array $data
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function display($template = '', $data = []) {
        return $this->twig->display($template.'.twig', $data);
    }

    /**
     * @param string $template
     * @param array $data
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function view($template = '', $data = []) {
        return $this->display($template, $data);
    }

    public function render() {}


    /**
     * Загрузить функции CI в шаблон
     */
    private function load_function()
    {
        $functions = ['form_open', 'form_close', 'form_input', 'form_dropdown', 'set_value', 'site_url', 'current_url'];

        // loader
        foreach ($functions as $func_name) {
            $this->twig->addFunction(
                new \Twig\TwigFunction($func_name, $func_name)
            );
        }
    }

}