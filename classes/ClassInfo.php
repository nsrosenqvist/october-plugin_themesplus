<?php namespace Nsrosenqvist\ThemesPlus\Classes;

use File;

class ClassInfo {

    protected $tokens = [];
    protected $lockedTokens = [];

    // The only info we extract
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

        // Loop through the list of tokens we're looking after
        foreach ($this->tokensOfInterest as $key => $value)
        {
            // Compare with the tokens in the file
            foreach ($tokens as $token)
            {
                // If a token has been found (which shouldn't exist twice) we skip it
                if (is_array($token) && in_array($token[0], $this->lockedTokens))
                    continue;

                // Check if it's a token we're looking for
                if (is_array($token) && $token[0] == $key)
                {
                    $this->tokensOfInterest[$key] = true;

                    // If it's a namespace we need flag it so we can process several tokens
                    if (in_array($token[0], [T_NAMESPACE, T_EXTENDS]))
                    {
                        $gettingNamespace = true;
                        $namespacedToken = $token[0];
                        $tokensFound[$namespacedToken] = "";
                    }
                }
                else
                {
                    // Check if should continue extracting a namespace
                    if ($gettingNamespace)
                    {
                        if (is_array($token) && in_array($token[0], [T_STRING, T_NS_SEPARATOR]))
                        {
                            $tokensFound[$namespacedToken] .= $token[1];
                        }
                        // Stop processing the namespace if we find either a ; or a {
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
                        // Save the token if it's one we've been searching for
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

    // Custom getter so that we can access found tokens like class properties
    public function __get($name)
    {
        $token = constant("T_".strtoupper($name));

        if (isset($this->tokens[$token]))
            return $this->tokens[$token];

        return null;
    }
}
