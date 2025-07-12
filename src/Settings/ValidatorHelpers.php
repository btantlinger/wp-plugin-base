<?php

namespace WebMoves\PluginBase\Settings;

trait ValidatorHelpers
{
    abstract protected function get_text_domain(): string;

    protected function required(): callable
    {
        return FieldValidators::required($this->get_text_domain());
    }

    protected function email(): callable
    {
        return FieldValidators::email($this->get_text_domain());
    }

    protected function url(): callable
    {
        return FieldValidators::url($this->get_text_domain());
    }

    protected function min_length(int $min_length): callable
    {
        return FieldValidators::min_length($min_length, $this->get_text_domain());
    }

    protected function max_length(int $max_length): callable
    {
        return FieldValidators::max_length($max_length, $this->get_text_domain());
    }

    protected function number_range(int $min = null, int $max = null): callable
    {
        return FieldValidators::number_range($min, $max, $this->get_text_domain());
    }
}