<?php

namespace Pantheon\Terminus\UnitTests\Commands\Auth;

use Pantheon\Terminus\Collections\SavedTokens;
use Pantheon\Terminus\Commands\Auth\LoginCommand;
use Pantheon\Terminus\Models\SavedToken;

class LoginCommandTest extends AuthTest
{
    /**
     * @var SavedToken
     */
    private $token;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->token = $this->getMockBuilder(SavedToken::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->token->session = $this->session;
        $this->session->tokens = $this->getMockBuilder(SavedTokens::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->command = new LoginCommand();
        $this->command->setConfig($this->config);
        $this->command->setLogger($this->logger);
        $this->command->setSession($this->session);
    }

    /**
     * Exercsies LoginCommand::logIn where the machine token is explicitly given
     */
    public function testLogInWithMachineToken()
    {
        $token_string = 'token_string';

        $this->session->tokens->expects($this->once())
            ->method('get')
            ->with($this->equalTo($token_string))
            ->will($this->throwException(new \Exception));
        $this->logger->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo('Logging in via machine token.')
            );
        $this->session->tokens->expects($this->once())
            ->method('create')
            ->with($this->equalTo($token_string));

        $out = $this->command->logIn(['machine-token' => $token_string,]);
        $this->assertNull($out);
    }

    /**
     * Exercises LoginCommand::logIn where the email address referencing a saved machine token is given
     */
    public function testLogInWithEmail()
    {
        $email = "email@ddr.ess";

        $this->session->tokens->expects($this->once())
            ->method('get')
            ->with($this->equalTo($email))
            ->willReturn($this->token);
        $this->logger->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo('Logging in via machine token.')
            );
        $this->token->expects($this->once())
            ->method('logIn')
            ->with();

        $out = $this->command->logIn(compact('email'));
        $this->assertNull($out);
    }

    /**
     * Exercises LoginCommand::logIn when no info is given but a single machine token has been saved
     */
    public function testLogInWithSoloSavedToken()
    {
        $email = "email@ddr.ess";

        $this->session->tokens->expects($this->once())
            ->method('all')
            ->with()
            ->willReturn([$this->token,]);
        $this->token->expects($this->once())
            ->method('get')
            ->with($this->equalTo('email'))
            ->willReturn($email);
        $this->logger->expects($this->exactly(2))
            ->method('log')
            ->withConsecutive(
                [
                    $this->equalTo('notice'),
                    $this->equalTo('Found a machine token for {email}.'),
                    $this->equalTo(compact('email'))
                ],
                [
                    $this->equalTo('notice'),
                    $this->equalTo('Logging in via machine token.')
                ]
            );
        $this->token->expects($this->once())
            ->method('logIn');

        $out = $this->command->logIn();
        $this->assertNull($out);
    }

    /**
     * Exercises LoginCommand::logIn when no data was given and there are no saved machine tokens
     *
     * @expectedException \Terminus\Exceptions\TerminusException
     * @expectedExceptionMessage Please visit the dashboard to generate a machine token:
     */
    public function testCannotLogInWithoutTokens()
    {
        $this->session->tokens->expects($this->once())
            ->method('all')->willReturn([]);

        $out = $this->command->logIn();
        $this->assertNull($out);
    }

    /**
     * Exercises LoginCommand::logIn when no data was given and there are multiple saved machine tokens
     *
     * @expectedException \Terminus\Exceptions\TerminusException
     * @expectedExceptionMessage Tokens were saved for the following email addresses:
     */
    public function testCannotLogInWithoutIndicatingWhichToken()
    {
        $this->session->tokens->expects($this->once())
            ->method('all')
            ->willReturn([$this->token, $this->token,]);
        $this->session->tokens->expects($this->once())
            ->method('ids')
            ->willReturn(['token1', 'token2',]);

        $out = $this->command->logIn();
        $this->assertNull($out);
    }
}
