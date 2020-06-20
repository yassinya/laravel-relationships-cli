<?php

namespace Yassinya\Relation;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class RelationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'relation 
                            {main-model : The main model name} 
                            {relation-type : Type of the relationship} 
                            {target-model : The target model name} 
                            {--p|polymorphic : Make it a polymorphic relationship}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Defines eloquent relationships between models';

    /**
     * Main model
     *
     * @var string
     */
    protected $mainModel;

    /**
     * The target model
     *
     * @var string
     */
    protected $targetModel;

    private const ONE_TO_ONE = 1;
    private const ONE_TO_MANY = 2;
    private const MANY_TO_MANY = 3;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
        $this->composer = app()['composer'];

        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->mainModel = ucfirst($this->argument('main-model'));
        $this->targetModel = ucfirst($this->argument('target-model'));

        $this->defineRelation();
        $this->defineRelation(true);

    }

    /**
     * Creates 
     */
    private function defineRelation($inverse = false)
    {
        $model = $inverse ? $this->targetModel : $this->mainModel;

        $method = $this->buildMethod($this->getMethodName($inverse), $inverse);

        $modelFile = base_path().'/' . $this->getModelsOutputPath() . '/' . ucfirst($model) .'.php';
        $content = $this->getModelContent($modelFile, $model);
        $content = $this->insertMethod($content, $method);

        $this->filesystem->put($modelFile, $content);

        $this->info('Created '.$modelFile);
    }


    /**
     * Get model file content
     * @param string $path
     * @param string $model
     * @return string
     */
    private function getModelContent($path, $model)
    {
        if(! file_exists($path)){
            return TemplateCompiler::compile($this->getModelTemplate(), [
                '{{class}}' => ucfirst($model),
                '{{namespace}}' => $this->getNamespace(),
            ]);
        }else{
            return $this->filesystem->get($path);
        }
    }

    /**
     * Get model template content
     * @return string
     */
    private function getModelTemplate()
    {
        return $this->filesystem->get(__DIR__.'./templates/model.txt');
    }

    /**
     * Get model namespace
     * @return string
     */
    private function getNamespace()
    {
        return config('relation.namespace');
    }

    /**
     * Get models output path
     * @return string
     */
    private function getModelsOutputPath()
    {
        return config('relation.models-path');
    }

    /**
     * Get method template content
     * @return string
     */
    private function getMethodTemplate()
    {
        return $this->filesystem->get(__DIR__.'./templates/method.txt');
    }

    /**
     * Compile model method template
     * @param string $name
     * @param boolean $inverse
     * @return string
     */
    private function buildMethod($name, $inverse = false)
    {
        return TemplateCompiler::compile($this->getMethodTemplate(), [
            '{{name}}' => strtolower($name),
            '{{relation-method}}' => $this->getEloquentRelationshipMethod($inverse),
            '{{args}}' => $this->getEloquentRelationshipMethodArgs($inverse),
        ]);
    }

    /**
     * Get eloquent relationship method name
     * @param boolean $inverse
     * @return string
     */
    private function getEloquentRelationshipMethod($inverse = false)
    {
        switch ($this->getRelation()) {
            case self::ONE_TO_ONE:
                if($this->option('polymorphic')) return $inverse ? 'morphTo' : 'morphOne';

                return $inverse ? 'belongsTo' : 'hasOne';
            case self::ONE_TO_MANY:
                if($this->option('polymorphic')) return $inverse ? 'morphTo' : 'morphMany';

                return $inverse ? 'belongsTo' : 'hasMany';            
            case self::MANY_TO_MANY:
                if($this->option('polymorphic')) return $inverse ? 'morphedByMany' : 'morphToMany';

                return 'belongsToMany';            
            default:
                return null;
        }
    }

    /**
     * Get eloquent relationship method arguments
     * @param boolean $inverse
     * @return string
     */
    private function getEloquentRelationshipMethodArgs($inverse = false)
    {
        $arg1 = "'" . $this->getNamespace() . "\\" . ucfirst($inverse ? $this->mainModel : $this->targetModel) . "'";
        $arg2 = "'" . strtolower($this->targetModel) . "able" . "'";
        if ($this->option('polymorphic')) {
            if($this->getRelation() === self::ONE_TO_ONE || $this->getRelation() === self::ONE_TO_MANY){
                return $inverse ? '' :  $arg1 . ', ' . $arg2;
            }

            return $arg1 . ', ' . $arg2;
        }

        return $arg1;        
    }

    /**
     * Get model method name
     * @param boolean $inverse
     * @return string
     */
    private function getMethodName($inverse = false)
    {
        $modelName = $inverse ? $this->mainModel : $this->targetModel;

        if($this->option('polymorphic') && $inverse && $this->getRelation() !== self::MANY_TO_MANY) 
        {
            return strtolower($this->targetModel) . 'able';
        }

        switch ($this->getRelation()) {
            case self::ONE_TO_ONE:
                return strtolower($modelName);           
            case self::ONE_TO_MANY:
                return $inverse ? strtolower($modelName) : Str::plural($modelName);           
            case self::MANY_TO_MANY:
                return Str::plural($modelName);           
            default:
                return null;
        }
    }

    /**
     * Get relationship method type
     * @return integer
     */
    private function getRelation()
    {
        switch ($this->argument('relation-type')) {
            case '121':
                return self::ONE_TO_ONE;            
            case '12m':
                return self::ONE_TO_MANY;            
            case 'm2m':
                return self::MANY_TO_MANY;            
            default:
                return null;
        }
    }

    /**
     * Add method to model
     * @param string $modelContent
     * @param string $methodContent
     * @return string
     */
    private function insertMethod($modelContent, $methodContent)
    {
        // Find the index of the last closing curly brace {
        $lastClosingCurlyBrace = strrpos($modelContent, '}');
        return substr_replace($modelContent, "\n" . $methodContent, $lastClosingCurlyBrace-1, 0);
    }
}