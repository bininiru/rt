<?php

namespace App\Services;


use App\Models\Intent as IntentModel;
use Google\ApiCore\ApiException;
use Google\Cloud\Dialogflow\V2\Intent;
use Google\Cloud\Dialogflow\V2\IntentView;
use Google\Cloud\Dialogflow\V2\IntentsClient;
use Google\Cloud\Dialogflow\V2\Intent\Message;
use Google\Cloud\Dialogflow\V2\Intent\Message\Text;
use Google\Cloud\Dialogflow\V2\Intent\TrainingPhrase;
use Google\Cloud\Dialogflow\V2\Intent\TrainingPhrase\Part;
use Google\Cloud\Dialogflow\V2\AgentsClient;
use Illuminate\Support\Facades\Log;

class DialogflowService
{
    /** @var IntentsClient $intents_client */
    protected $intents_client;
    protected $formatted_parent;

    private const AGENT_NAME = 'newagent-qwyp';

    public function __construct()
    {
        $file_path = storage_path('credentials/newagent-qwyp-f1afbbd5ca18.json');
        putenv('GOOGLE_APPLICATION_CREDENTIALS='.$file_path);

        $this->intents_client = new IntentsClient([
            'credentialsConfig' => [
                'keyFile' => $file_path,
            ],
        ]);
        $this->formatted_parent = $this->intents_client->agentName(self::AGENT_NAME);
    }

    public function __destruct()
    {
        $this->intents_client->close();
    }

    public function storeIntent(IntentModel $intent_model)
    {
        $training_phrases_input = $intent_model->questions->pluck('phrase')->toArray();
        $answers = $intent_model->answers->pluck('phrase')->toArray();

        $training_phrases = [];
        foreach ($training_phrases_input as $training_phrase_part) {
            $part = new Part();
            $part->setText($training_phrase_part);
            $training_phrase = new TrainingPhrase();
            $training_phrase->setParts([$part]);
            $training_phrases[] = $training_phrase;
        }

        $text = new Text();
        $text->setText($answers);
        $message = new Message();
        $message->setText($text);

        if ($intent_model->external_id) {
            $formatted_name = $this->intents_client->intentName(self::AGENT_NAME, $intent_model->external_id);
            $intent = $this->intents_client->getIntent($formatted_name);
        } else {
            $intent = new Intent();
        }

        $intent->setDisplayName($intent_model->name);
        $intent->setTrainingPhrases($training_phrases);
        $intent->setMessages([$message]);

        if ($intent_model->external_id) {
            try {
                $this->intents_client->updateIntent($intent);
            } catch (ApiException $e) {
                Log::error('Dialogflow insert intent error', [
                    'msg' => $e->getMessage(),
                ]);

                return null;
            }
        } else {
            try {
                $response = $this->intents_client->createIntent($this->formatted_parent, $intent);
            } catch (ApiException $e) {
                Log::error('Dialogflow insert intent error', [
                    'msg' => $e->getMessage(),
                ]);

                return null;
            }

            $intent_model->external_id = collect(explode('/',$response->getName()))->last();
            $intent_model->save();
        }

        Log::info('Dialogflow store intent success', [
            'id' => $intent_model->id
        ]);
    }
}
