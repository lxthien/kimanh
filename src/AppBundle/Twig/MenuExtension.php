<?php

namespace AppBundle\Twig;

use AppBundle\Service\MenuService;
use AppBundle\Entity\Menu;

class MenuExtension extends \Twig_Extension
{
    private $menuService;

    public function __construct(MenuService $menuService)
    {
        $this->menuService = $menuService;
    }

    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('render_menu', [$this, 'renderMenu'], ['is_safe' => ['html']]),
            new \Twig_SimpleFunction('get_menu', [$this, 'getMenu']),
        ];
    }

    /**
     * Render a menu by name
     *
     * @param string $menuName
     * @param array $options
     * @return string
     */
    public function renderMenu($menuName, array $options = [])
    {
        $menu = $this->menuService->getMenuByName($menuName);
        
        if (!$menu) {
            return '';
        }

        return $this->menuService->renderMenu($menu, $options);
    }

    /**
     * Get a menu object by name
     *
     * @param string $menuName
     * @return Menu|null
     */
    public function getMenu($menuName)
    {
        return $this->menuService->getMenuByName($menuName);
    }

    public function getName()
    {
        return 'menu_extension';
    }
}
