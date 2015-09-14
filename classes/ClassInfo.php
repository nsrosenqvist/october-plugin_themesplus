<?php namespace Nsrosenqvist\ThemesPlus\Classes;

use File;

class ClassInfo {

    protected $tokens = [];
    protected $lockedTokens = [];

    protected $tokensOfInterest = [
        T_CLASS => false,
        T_EXTENDS => false,
        T_NAMESPACE => false
    ];

    public function __construct($file)
    {
        $this->tokens = $this->parseFile($file);
    }

    public function parseFile($file)
    {
        if ( ! File::exists($file))
            return false;

        $content = File::get($file);
        $tokens = token_get_all($content);
        $gettingNamespace = false;
        $namespacedToken = "";
        $tokensFound = [];
        $symbols = "";

        foreach ($this->tokensOfInterest as $key => $value)
        {
            foreach ($tokens as $token)
            {
                if (is_array($token) && in_array($token[0], $this->lockedTokens))
                    continue;

                if (is_array($token) && $token[0] == $key)
                {
                    $this->tokensOfInterest[$key] = true;

                    if (in_array($token[0], [T_NAMESPACE, T_EXTENDS]))
                    {
                        $gettingNamespace = true;
                        $namespacedToken = $token[0];
                        $tokensFound[$namespacedToken] = "";
                    }
                }
                else
                {
                    if ($gettingNamespace)
                    {
                        if (is_array($token) && in_array($token[0], [T_STRING, T_NS_SEPARATOR]))
                        {
                            $tokensFound[$namespacedToken] .= $token[1];
                        }
                        elseif (! is_array($token) && in_array($token, [';','{']))
                        {
                            $symbols .= " ".$token;
                            $this->tokensOfInterest[$namespacedToken] = false;
                            $gettingNamespace = false;
                            $this->lockedTokens[] = $namespacedToken;
                        }
                    }
                    else
                    {
                        if (is_array($token) && $this->tokensOfInterest[$key] && $token[0] == T_STRING)
                        {
                            $tokensFound[$key] = $token[1];
                            $this->tokensOfInterest[$key] = false;

                            if ($key == T_CLASS)
                                $this->lockedTokens[] = $key;
                        }
                    }
                }
            }
        }

        return $tokensFound;
    }

    public function __get($name)
    {
        $token = constant("T_".strtoupper($name));

        if (isset($this->tokens[$token]))
            return $this->tokens[$token];

        return null;
    }
}
