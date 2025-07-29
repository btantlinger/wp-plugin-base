<?php

namespace WebMoves\PluginBase\Contracts\Controllers;

interface FormController extends Controller
{
    /**
     * Generate hidden form fields for POST/GET requests
     *
     * @param array $additional_fields Additional hidden fields to include
     * @return string HTML for hidden fields including action and nonce
     */
    public function get_action_fields(array $additional_fields = []): string;

}