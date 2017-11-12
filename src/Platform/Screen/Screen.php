<?php

namespace Orchid\Platform\Screen;

use Illuminate\Http\Request;

abstract class Screen
{

    /**
     * Display header name
     *
     * @var string
     */
    public $name;

    /**
     * Display header description
     *
     * @var string
     */
    public $description;
    /**
     * @var array|Request|string
     */
    public $request;
    /**
     * @var array
     */
    private $arguments = [];

    /**
     * Screen constructor.
     */
    public function __construct()
    {
        $this->request = request();
    }

    /**
     * Button commands
     *
     * @return array
     */
    public function commandBar() : array
    {
        return [];
    }

    /**
     * Query data
     *
     * @return array
     */
    public function query() : array
    {
        return [];
    }

    /**
     * @return array
     */
    public function build() : array
    {
        $query = call_user_func_array([$this, 'query'], $this->arguments);

        foreach ($this->layout() as $layout) {
            $post = new Repository($query[$layout]);
            $build[] = (new $layout)->build($post);
        }

        return $build ?? [];
    }

    /**
     * Views
     *
     * @return array
     */
    public function layout() : array
    {
        return [];
    }

    /**
     * @param $method
     * @param $parameters
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View|mixed
     */
    public function handle($method = null, $parameters = null)
    {
        if ($this->request->method() === 'GET' || (is_null($method) && is_null($parameters))) {
            if (!is_array($method)) {
                $method = [$method];
            }
            $this->arguments = $method;

            return $this->view();
        }


        if (!is_null($parameters)) {
            if (!is_array($method)) {
                $method = [$method];
            }
            $this->arguments = $method;

            $this->reflectionParams($parameters);

            return call_user_func_array([$this, $parameters], $this->arguments);
        }


        if (!is_array($parameters)) {
            $parameters = [$parameters];
        }
        $this->arguments = $parameters;

        $this->reflectionParams($method);

        return call_user_func_array([$this, $method], $this->arguments);
    }


    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function view()
    {
        return view('dashboard::container.layouts.base', [
            'name'        => $this->name,
            'description' => $this->description,
            'arguments'   => $this->arguments,
            'screen'      => $this,
        ]);
    }

    /**
     * @param $method
     */
    public function reflectionParams($method)
    {
        $class = new \ReflectionClass($this);

        foreach ($class->getMethod($method)->getParameters() as $key => $parameter) {
            if (!is_null($parameter->getClass())) {
                $arg[] = app()->make($parameter->getClass()->name);
            }
        }

        $this->arguments = array_merge($arg ?? [], $this->arguments);
    }

}
