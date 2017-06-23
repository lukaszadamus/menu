<?php
/**
 * Contains the Laravel service provider class.
 *
 * @author      Lavary
 * @author      Attila Fulop
 * @license     MIT
 * @since       2017-06-16
 *
 */

namespace Konekt\Menu;

use Illuminate\Support\ServiceProvider;
use Illuminate\View\Compilers\BladeCompiler;
use Konekt\Menu\Renderers\DivItemRenderer;
use Konekt\Menu\Renderers\DivMenuRenderer;
use Konekt\Menu\Renderers\LiItemRenderer;
use Konekt\Menu\Renderers\OlMenuRenderer;
use Konekt\Menu\Renderers\UlMenuRenderer;

class MenuServiceProvider extends ServiceProvider
{

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/settings.php', 'menu.settings');
        $this->mergeConfigFrom(__DIR__ . '/../config/views.php', 'menu.views');

        $this->app->singleton('menu', function ($app) {
            return new Repository();
        });

        $this->app->singleton('konekt.menu.renderer.menu.ul', UlMenuRenderer::class);
        $this->app->singleton('konekt.menu.renderer.menu.ol', OlMenuRenderer::class);
        $this->app->singleton('konekt.menu.renderer.menu.div', DivMenuRenderer::class);
        $this->app->singleton('konekt.menu.renderer.item.li', LiItemRenderer::class);
        $this->app->singleton('konekt.menu.renderer.item.div', DivItemRenderer::class);

        $this->registerBladeExtensions();
    }

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'menu');

        $this->publishes([
            __DIR__ . '/../resources/views'     => base_path('resources/views/vendor/menu'),
            __DIR__ . '/../config/settings.php' => config_path('menu_settings.php'),
            __DIR__ . '/../config/views.php'    => config_path('menu_views.php'),
        ]);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['menu'];
    }

    protected function registerBladeExtensions()
    {
        $this->app->afterResolving('blade.compiler', function (BladeCompiler $bladeCompiler) {

            /*
            |--------------------------------------------------------------------------
            | @lm-attrs
            |--------------------------------------------------------------------------
            |
            | Buffers the output if there's any.
            | The output will be passed to mergeStatic()
            | where it is merged with item's attributes
            |
            */
            $bladeCompiler->extend( function($view, $compiler){
                $pattern = '/(\s*)@lm-attrs\s*\((\$[^)]+)\)/';
                return preg_replace($pattern,
                    '$1<?php $lm_attrs = $2->attr(); ob_start(); ?>',
                    $view);
            });

            /*
            |--------------------------------------------------------------------------
            | @lm-endattrs
            |--------------------------------------------------------------------------
            |
            | Reads the buffer data using ob_get_clean()
            | and passes it to MergeStatic().
            | mergeStatic() takes the static string,
            | converts it into a normal array and merges it with others.
            |
            */
            $bladeCompiler->extend( function($view, $compiler){

                $pattern = '/(?<!\w)(\s*)@lm-endattrs(\s*)/';
                return preg_replace($pattern,
                    '$1<?php echo \Konekt\Menu\Builder::mergeStatic(ob_get_clean(), $lm_attrs); ?>$2',
                    $view);
            });

        });
    }

}
