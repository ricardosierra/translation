<?php

namespace Translation;

class Factory extends \Illuminate\Validation\Factory
{

    // Change this method
    /**
     * Resolve a new Validator instance.
     *
     * @param  array $data
     * @param  array $rules
     * @param  array $messages
     * @return \MyLib\Validation\Validator
     */
    protected function resolve($data, $rules, $messages)
    {
        if (is_null($this->resolver)) {
            // THIS WILL NOW RETURN YOUR NEW SERVICE PROVIDER SINCE YOU'RE
            // IN THE MyLib\Validation NAMESPACE
            return new Validator($this->translator, $data, $rules, $messages);
        }
        else
        {
            return call_user_func($this->resolver, $this->translator, $data, $rules, $messages);
        }
    }

}