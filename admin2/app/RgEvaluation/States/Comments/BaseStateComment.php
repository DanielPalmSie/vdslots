<?php

namespace App\RgEvaluation\States\Comments;

use App\Repositories\UserCommentRepository;
use App\RgEvaluation\Triggers\TriggerInterface;
use Silex\Application;

abstract class BaseStateComment implements StateCommentInterface
{
    protected const RG_EVALUATION_TAG = 'rg-evaluation';
    protected const SEARCH_MASK = "{{%s}}";
    private TriggerInterface $trigger;
    private array $variables = [];
    private Application $app;

    public function __construct(Application $app, TriggerInterface $trigger)
    {
        $this->app = $app;
        $this->trigger = $trigger;
    }

    public function setTrigger(TriggerInterface $trigger): StateCommentInterface
    {
        $this->trigger = $trigger;
        return $this;
    }

    public function getTrigger(): TriggerInterface
    {
        return $this->trigger;
    }

    public function getCommentContext(): string
    {
        $step = $this->getTrigger()->getRgEvaluation()->step;
        $state = class_basename($this->getTrigger()->getCurrentState());
        $trigger = class_basename($this->getTrigger());
        $type = $this->getCommentType();
        $commentTemplate = $this->app['rg-evaluation-comments'];

        $triggerContext = $commentTemplate['steps'][$step]['states'][$state]['triggers'][$trigger][$type] ?? null;
        $defaultContext = $commentTemplate['steps'][$step]['states'][$state]['default'][$type] ?? null;
        $context = $triggerContext ?? $defaultContext;

        if (!$context) {
            $context = "No comment context defined for $step/$state/$trigger/$type";
        }
        $search = array_map(function ($key) {
            return sprintf(static::SEARCH_MASK, $key);
        }, array_keys($this->getVariables()));

        return $trigger . " evaluation: " . str_replace($search, $this->getVariables(), $context);
    }

    public function setVariables(array $variables): StateCommentInterface
    {
        $this->variables = $variables;
        return $this;
    }

    public function getVariables(): array
    {
        return $this->variables;
    }

    public function addComment(string $comment): void
    {
        UserCommentRepository::createComment([
            'user_id' => $this->getTrigger()->getRgEvaluation()->user->id,
            'tag' => static::RG_EVALUATION_TAG,
            'comment' => $comment
        ], false);
    }

    abstract protected function getCommentType(): string;
}