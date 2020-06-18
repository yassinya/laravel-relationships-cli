<?php

namespace Yassinya\Relation;

class TemplateCompiler
{
    /**
     * Compile the template according to data
     *
     * @param $template
     * @param $data
     * @return mixed
     */
    public static function compile($template, $data)
    {
        foreach($data as $placeholder => $replacement)
        {
            $template = str_replace($placeholder, $replacement, $template);
        }

        return $template;
    }
}