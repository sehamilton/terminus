<?php

namespace Pantheon\Terminus\Commands\Auth;

use Pantheon\Terminus\Commands\TerminusCommand;
use Terminus\Exceptions\TerminusException;

class LoginCommand extends TerminusCommand
{

    /**
     * Logs a user into Pantheon
     *
     * @command auth:login
     * @aliases login
     *
     * @option machine-token A machine token to be saved for future logins
     * @usage terminus auth:login --machine-token=111111111111111111111111111111111111111111111
     *   Logs in the user granted machine token "111111111111111111111111111111111111111111111"
     * @usage terminus auth:login
     *   Logs in your user with a previously saved machine token
     * @usage terminus auth:login --email=<email_address>
     *   Logs in your user with a previously saved machine token belonging to the account linked to the given email
     */
    public function logIn(array $options = ['machine-token' => null, 'email' => null,])
    {
        $tokens = $this->session()->tokens;

        if (isset($options['machine-token']) && !is_null($token_string = $options['machine-token'])) {
            try {
                $token = $tokens->get($token_string);
            } catch (\Exception $e) {
                $this->log()->notice('Logging in via machine token.');
                $tokens->create($token_string);
            }
        } elseif (isset($options['email']) && !is_null($email = $options['email'])) {
            $token = $tokens->get($email);
        } elseif (count($all_tokens = $tokens->all()) == 1) {
            $token = array_shift($all_tokens);
            $this->log()->notice('Found a machine token for {email}.', ['email' => $token->get('email'),]);
        } else {
            if (count($all_tokens) > 1) {
                throw new TerminusException(
                    "Tokens were saved for the following email addresses:\n{tokens}\nYou may log in via `terminus"
                        . " auth:login --email=<email>`, or you may visit the dashboard to generate a machine"
                        . " token:\n{url}",
                    ['tokens' => implode("\n", $tokens->ids()), 'url' => $this->getMachineTokenCreationURL(),]
                );
            } else {
                throw new TerminusException(
                    "Please visit the dashboard to generate a machine token:\n{url}",
                    ['url' => $this->getMachineTokenCreationURL(),]
                );
            }
        }
        if (isset($token)) {
            $this->log()->notice('Logging in via machine token.');
            $token->logIn();
        }
    }

    /**
     * Generates the URL string for where to create a machine token
     *
     * @return string
     */
    private function getMachineTokenCreationURL()
    {
        return vsprintf(
            '%s://%s/machine-token/create/%s',
            [
                $this->config->get('dashboard_protocol'),
                $this->config->get('dashboard_host'),
                gethostname(),
            ]
        );
    }
}
