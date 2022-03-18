<?php

namespace App\Http\Controllers;

use Faker\Extension\ContainerBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use LINE\LINEBot\Event\MessageEvent;
use LINE\LINEBot\Event\MessageEvent\TextMessage;
use LINE\LINEBot\Exception\InvalidEventRequestException;
use LINE\LINEBot\Exception\InvalidSignatureException;
use LINE\LINEBot\MessageBuilder\FlexMessageBuilder;
use LINE\LINEBot\MessageBuilder\ImageMessageBuilder;
use LINE\LINEBot\MessageBuilder\MultiMessageBuilder;
use LINE\LINEBot\MessageBuilder\RawMessageBuilder;
use LINE\LINEBot\MessageBuilder\StickerMessageBuilder;
use LINE\LINEBot\MessageBuilder\TextMessageBuilder;

class LineController extends Controller
{
    public function webhook(Request $request) {
        $httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient(config('line.channel_access_token'));
        $bot = new \LINE\LINEBot($httpClient, ['channelSecret' => config('line.channel_secret')]);

        $signature = $request->header(\LINE\LINEBot\Constant\HTTPHeader::LINE_SIGNATURE);
        if (empty($signature)) {
            abort(400);
        }

        Log::info($request->getContent());

        try {
            // get user messages
            $events = $bot->parseEventRequest($request->getContent(), $signature);
        } catch (InvalidSignatureException $e) {
            Log::error('Invalid signature');
            abort(400, 'Invalid signature');
        } catch (InvalidEventRequestException $e) {
            Log::error('Invalid event request');
            abort(400, 'Invalid event request');
        }

        foreach ($events as $event) {
            if (!($event instanceof MessageEvent)) {
                Log::info('Non message event has come');
                continue;
            }

            if (!($event instanceof TextMessage)) {
                Log::info('Non text message has come');
                continue;
            }
            $inputText = $event->getText();
            $replyText = '';

            // command
            if ($inputText === 'ดูคะแนน') {
                $replyText = 'คะแนนของคุณคือ 100 คะแนน';
            } else {
                Log::info('inputText: ' . $inputText);
            }

            $replyToken = $event->getReplyToken();
            $userId = $event->getUserId();
            $profile = $bot->getProfile($userId);
            $profile = $profile->getJSONDecodedBody();
            $displayName = $profile['displayName'];
            $pictureUrl = $profile['pictureUrl'];
            $statusMessage = $profile['statusMessage'];

            if ($replyText !== '') {
                $response = $bot->replyText($replyToken, $replyText);

                Log::info($response->getHTTPStatus().':'.$response->getRawBody());
            } else {
                $flexDataJson2 = <<<JSON
{
"type": "flex",
	  "altText": "Flex Message",
	  "contents": {
  "type": "bubble",
  "body": {
    "type": "box",
    "layout": "vertical",
    "contents": [
      {
        "type": "box",
        "layout": "vertical",
        "contents": [
          {
            "type": "image",
            "url": "https://sv1.picz.in.th/images/2020/02/29/x8qxLa.jpg",
            "aspectRatio": "341:148",
            "size": "full"
          }
        ],
        "position": "absolute",
        "height": "110px",
        "width": "260px",
        "offsetTop": "22px",
        "offsetStart": "22px"
      },
      {
        "type": "box",
        "layout": "vertical",
        "contents": [
          {
            "type": "box",
            "layout": "vertical",
            "contents": [
              {
                "type": "text",
                "text": "$inputText",
                "align": "center",
                "size": "4xl",
                "weight": "bold"
              }
            ]
          },
          {
            "type": "box",
            "layout": "vertical",
            "contents": [
              {
                "type": "text",
                "text": "กรุงเทพมหานคร",
                "align": "center",
                "size": "xl"
              }
            ]
          }
        ],
        "borderWidth": "5px",
        "borderColor": "#000000",
        "cornerRadius": "10px",
        "paddingAll": "5px"
      }
    ],
    "borderColor": "#000000"
  }
}
}
JSON;

                $multiMessageBuilder = new MultiMessageBuilder();
                $multiMessageBuilder->add(new TextMessageBuilder($displayName));
                $multiMessageBuilder->add(new TextMessageBuilder($statusMessage));
                $multiMessageBuilder->add(new ImageMessageBuilder($pictureUrl, $pictureUrl));
                $multiMessageBuilder->add(new StickerMessageBuilder("11537", "52002744"));
                $multiMessageBuilder->add(new RawMessageBuilder(json_decode($flexDataJson2, true)));
                $response = $bot->replyMessage($replyToken, $multiMessageBuilder);

            }
        }
        return response()->json([]);
    }
}
