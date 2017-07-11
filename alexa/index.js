'use strict';

var http = require('http');

/**
 * TODO Wording rewrite website/shop to store
 * TODO Refactoring : call token with getApiOption like in getAverageShoppingCart
 *
 * For additional samples, visit the Alexa Skills Kit Getting Started guide at
 * http://amzn.to/1LGWsLG
 *
 * @author    Auriau Maxime <maxime.auriau@dnd.fr>
 * @copyright Copyright (c) 2017 Agence Dn'D
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      http://www.dnd.fr/
 *
 */
var sentence = {
    basicSpeechOutput: "Please tell ask me about your revenue such as : What is my revenue for default, since the last month ? Or about the best product like : Tell me my best product. Or about the pending order such as : How many pending order I have ?",
    askName: "What is your name ?",
    speechOutputAbout: "your revenue, pending orders, best product, average basket, or available store."
};

// Root url of your orocommerce without http://
var oroHost = 'example.com';
// Basic path of your Oro Commerce
var oroApiPath = '/admin/api/rest/latest/';
// TO authentificat in basic http-auth and generate the wsse header to authtificat at the API (htpasswd)
var wsseHttpOptions = {
    auth: 'user:passwd',
    host: oroHost,
    path: '/scripts/generate-wsse-header.php',
    method: 'GET'
};


// --------------- Helpers that build all of the responses -----------------------
function buildSpeechletResponse(title, output, repromptText, shouldEndSession) {
    return {
        outputSpeech: {
            type: 'PlainText',
            text: output,
        },
        card: {
            type: 'Simple',
            title: `SessionSpeechlet - ${title}`,
            content: `SessionSpeechlet - ${output}`,
        },
        reprompt: {
            outputSpeech: {
                type: 'PlainText',
                text: repromptText,
            },
        },
        shouldEndSession,
    };
}

function buildCustomSpeechletResponse(title, output, repromptText, shouldEndSession) {
    return {
        outputSpeech: {
            type: 'SSML',
            ssml: output
        },
        card: {
            type: 'Simple',
            title: `SessionSpeechlet - ${title}`,
            content: `SessionSpeechlet - ${output}`,
        },
        reprompt: {
            outputSpeech: {
                type: 'PlainText',
                text: repromptText,
            },
        },
        shouldEndSession,
    };
}

function buildResponse(sessionAttributes, speechletResponse) {
    return {
        version: '1.0',
        sessionAttributes,
        response: speechletResponse,
    };
}


// --------------- Functions that control the skill's behavior -----------------------

function getWelcomeResponse(callback) {
    // If we wanted to initialize the session to have some attributes we could add those here.
    let sessionAttributes = {};
    let cardTitle = 'Welcome';
    let speechOutput = 'Welcome to oro commerce platform. ' + sentence.askName;
    // If the user either does not reply to the welcome message or says something that is not
    // understood, they will be prompted again with this text.
    let repromptText = sentence.askName;
    let shouldEndSession = false;
    generateNumber(function(response){
        sessionAttributes = {"gameNumber" : response};
    });
    sessionAttributes.gameCpt = 0;
    callback(sessionAttributes,
        buildSpeechletResponse(cardTitle, speechOutput, repromptText, shouldEndSession));
}

/**
 *
 * @param intent
 * @param session
 * @param callback
 */
function handleSessionEndRequest(intent, session, callback) {
    let sessionAttributes = session.attributes;
    let shouldEndSession = true;
    let cardTitle = 'Session Ended';
    let speechOutput = '';
    let name;
    name = getNameFromSession(session);
    speechOutput = `Thanks, for trying your Oro commerce B2B platform with Amazon echo. Have a nice day !`;
    if(intent.name == 'AMAZON.CancelIntent') {
        callback({}, 
            buildSpeechletResponse(cardTitle, null, null, shouldEndSession));
    }

    callback({}, 
        buildSpeechletResponse(cardTitle, speechOutput, null, shouldEndSession));
}

/**
 *
 * @param intent
 * @param session
 * @param callback
 */
function setNameInSession(intent, session, callback) {
    let cardTitle = intent.name;
    let nameSlot = intent.slots.first_name;
    let repromptText = '';
    let sessionAttributes = session.attributes;
    let shouldEndSession = false;
    let speechOutput = '';

    if (nameSlot) {
        let name = nameSlot.value;
        sessionAttributes.name = name;
        speechOutput = `<speak><say-as interpret-as="interjection">Bonjour</say-as> ${name}. Ask me about ` + sentence.speechOutputAbout + `</speak>`;
        repromptText = sentence.basicSpeechOutput;
    } else {
        speechOutput = "I'm not sure what your name is. Please try again.";
        repromptText = "I'm not sure what your name is. You can tell me your " +
            'name by saying, my name is Bob';
    }
    callback(sessionAttributes,
        buildCustomSpeechletResponse(cardTitle, speechOutput, repromptText, shouldEndSession));
}

/**
 * @param intent
 * @param session
 * @param callback
 */
function setShopInSession(intent, session, callback) {
    let cardTitle = intent.name;
    let shopSlot = intent.slots.shop;
    let repromptText = '';
    let sessionAttributes = session.attributes;
    let shouldEndSession = false;
    let speechOutput = '';
    let name;
    speechOutput = "I'm not sure what your shop is. Please try again.";
    repromptText = "I'm not sure what your shop is. You can tell me your shop by saying, The default website";
    name = getNameFromSession(session);
    sessionAttributes.name = name;
    let mainIntent = sessionAttributes.mainIntent;
    if (shopSlot) {
        if ('GetRevenues' == mainIntent) {
            sessionAttributes.previousIntent = intent.name;
            let shop = shopSlot.value;
            sessionAttributes.shop = shop;
            speechOutput = `<speak> ${name}. Since when do you want the revenue of the ${shop} store? For example, ask me : Since february </speak>`;
            repromptText = sentence.basicSpeechOutput;
        }   
    } 
    setTimeout(function(){
        callback(sessionAttributes,
            buildCustomSpeechletResponse(cardTitle, speechOutput, repromptText, shouldEndSession));
    },1500);
}

/**
 *
 * @param intent
 * @param session
 * @param callback
 */
function setMyDateInSession(intent, session, callback) {
    let cardTitle = intent.name;
    let dateSlot = intent.slots.date;
    let repromptText = '';
    let sessionAttributes = session.attributes;
    let shouldEndSession = false;
    let speechOutput = '';
    let name;
    let revenues;
    name = getNameFromSession(session);
    sessionAttributes.name = name;
    speechOutput = "<speak>I'm not sure what your date you pick. Please try again.</speak>";
    repromptText = "I'm not sure what your date you pick. You can tell me your date by saying, since april";
    if (dateSlot) {
        let mainIntent = sessionAttributes.mainIntent;
        if ('GetRevenues' == mainIntent) {
            let date = dateSlot.value;
            sessionAttributes.date = date;
            let previousIntent = sessionAttributes.previousIntent;
            if ('setShop' == previousIntent) {
                
                    getRevenueWithWebsiteAndDate(date, session, callback);
                
                //var website = getShopFromSession(session);

                //speechOutput = `<speak> ${name}, your revenue for the website of ${website} from <say-as interpret-as="date">${date}</say-as> is, ${revenues} €</speak>`;
                //sessionAttributes = {"name":name};
            } 
            else {
                 speechOutput = `<speak> ${name}. You ask me about the revenue of ${shop} website. Now I need a date such as : Since february. Or like : Since 4 may</speak>`;
                 repromptText = sentence.basicSpeechOutput;
            }
        }
    } 

    setTimeout(function(){
        callback(sessionAttributes,
            buildCustomSpeechletResponse(cardTitle, speechOutput, repromptText, shouldEndSession));
    },1100);
}

/**
 *
 * @param session
 * @returns {*}
 */
function getNameFromSession(session) {
    let name;

    if (session.attributes) {
        name = session.attributes.name;
    }

    if (name) {
        return name;
    }

    return 'My friend';
}
/**
 *
 * @param session
 * @returns {*}
 */
function getShopFromSession(session) {
    let shop;

    if (session.attributes) {
        shop = session.attributes.shop;
    }

    if (shop) {
        return shop;
    }

    return false;
}

/**
 *
 * @param session
 * @returns {*}
 */
function getMainIntentFromSession(session) {
    let mainIntent;

    if (session.attributes) {
        mainIntent = session.attributes.mainIntent;
    }

    if (mainIntent) {
        return mainIntent;
    }

    return 'NONE';
}

/**
 *
 * @param session
 * @returns {*}
 */
function getPreviousIntentFromSession(session) {
    let previousIntent;

    if (session.attributes) {
        previousIntent = session.attributes.previousIntent;
    }

    if (previousIntent) {
        return previousIntent;
    }

    return 'NONE';
}

/**
 *
 * @param intent
 * @param session
 * @param callback
 */
function getName(intent, session, callback) {
    let name;
    let repromptText = null;
    let sessionAttributes = session.attributes;
    name = getNameFromSession(session);
    sessionAttributes.name = name;    
    let shouldEndSession = false;
    let speechOutput = '';

    if (name) {
        speechOutput = `Your name is ${name}.`;
    } else {
        speechOutput = "I'm not sure who you are, you can say, my name" +
            ' is superman';
    }
    // Setting repromptText to null signifies that we do not want to reprompt the user.
    // If the user does not respond or says something that is not understood, the session
    // will end.
    callback(sessionAttributes,
        buildSpeechletResponse(intent.name, speechOutput, repromptText, shouldEndSession));
}

/**
 *
 * @param intent
 * @param session
 * @param callback
 * @constructor
 */
function GetRevenues(intent, session, callback) {
    let name;
    let cardTitle = 'get revenue';
    let repromptText = '';
    let sessionAttributes = session.attributes;
    let shouldEndSession = false;
    let speechOutput = '';
    name = getNameFromSession(session);
    sessionAttributes.mainIntent = intent.name;
    sessionAttributes.name = name;
    speechOutput = `<speak> ${name}, for which store do you want sales ? For example, ask me : The default store or, US Store.</speak>`;
    repromptText = `<speak> ${name}, for which store do you want sales ? For example, ask me : The default store or, US Store.</speak>`;
    callback(sessionAttributes,
        buildCustomSpeechletResponse(cardTitle, speechOutput, repromptText, shouldEndSession));
}

/**
 *
 * @param intent
 * @param session
 * @param callback
 * @constructor
 */
function GetRevenuesFrom(intent, session, callback) {
    let name;
    let from;

    let fromSlot = intent.slots.from;
    let sinceSlot = intent.slots.since;
    let cardTitle = 'get revenue';
    let repromptText = null;
    let sessionAttributes = session.attributes;
    let shouldEndSession = false;
    let speechOutput = '';
    let date1 = '';
    let date2 = '';
    name = getNameFromSession(session);
    sessionAttributes.name = name;
    if (fromSlot) {
        from = fromSlot.value;
        date1 = 'from';
    }
    if (sinceSlot) {
        from = sinceSlot.value;
        date1 = 'since';
        date2 = 'ago';
    }

    if (from) {
        var apiOption;
        getApiOption(function(response){
            apiOption = response;
            var baseUrl = oroApiPath + 'dnds/' + from + '/oro/api/total/revenue/from.json';
            apiOption.path = baseUrl;
            doCall(apiOption, false, function(response) {
                var revenues = response.revenue;
                from = response.date;
                from = from.replace(/-/g, '');
                speechOutput = `<speak> ${name}, your revenues ${date1} <say-as interpret-as="date">${from}</say-as> on your oro commerce platform, is ${revenues} € </speak>`;
                callback(sessionAttributes,
                    buildCustomSpeechletResponse(cardTitle, speechOutput, repromptText, shouldEndSession));

            });
        });
    } else {
        speechOutput = `<speak> ${name}, I dont understand the date given. Please ask me such as : Give me my revenue from march </speak>`;
        callback(sessionAttributes,
            buildCustomSpeechletResponse(cardTitle, speechOutput, repromptText, shouldEndSession));
    }
}

/**
 *
 * @param intent
 * @param session
 * @param callback
 */
function getMostSalesProduct(intent, session, callback) {
    let name;
    let cardTitle = 'get most sales';
    let repromptText = '';
    let sessionAttributes = session.attributes;
    let shouldEndSession = false;
    let speechOutput = '';
    name = getNameFromSession(session);
    sessionAttributes.name = name;
    var apiOption;
    getApiOption(function(response){
        apiOption = response;
        var baseUrl = oroApiPath + 'dnd/oro/api/the/most/sales/product.json';
        apiOption.path = baseUrl;
        doCall(apiOption, false, function(response) {
            speechOutput = 'There is no best product';
            if (response.products != false) {
                var sku = response.products.sku;
                var qty = response.products.qty;
                var productName = response.products.names;
                speechOutput = `<speak> ${name},  The best seller producton your oro commerce platform, is <emphasis level="strong">${productName}</emphasis> with, ${qty} sales !</speak>`;
            }

            callback(sessionAttributes,
                buildCustomSpeechletResponse(cardTitle, speechOutput, repromptText, shouldEndSession));
        });
    });
}

/**
 *
 * @param intent
 * @param session
 * @param callback
 */
function getMostSalesProductDate(intent, session, callback) {
    let name;
    let fromSlot = intent.slots.date;
    let from;
    let cardTitle = 'get most sales';
    let repromptText = null;
    let sessionAttributes = session.attributes;
    let shouldEndSession = false;
    let speechOutput = '';
    name = getNameFromSession(session);
    sessionAttributes.name = name;
    if (fromSlot) {
        from = fromSlot.value;
    }
    if (from) {
        var apiOption;
        getApiOption(function(response){
            apiOption = response;
            var baseUrl = oroApiPath + 'dnds/' + from + '/oro/api/the/most/sales/product/date.json';
            apiOption.path = baseUrl;
            doCall(apiOption, false, function(response) {
                from = response.date;
                from = from.replace(/-/g, '');                
                speechOutput = `<speak> There is no orders for, <say-as interpret-as="date">${from}</say-as></speak>`;
                if (response.products != false) {
                    var sku = response.products.sku;
                    var qty = response.products.qty;
                    var productName = response.products.names;
                    speechOutput = `<speak> ${name}, The most product sales  since <say-as interpret-as="date">${from}</say-as> on your oro commerce platform, is <emphasis level="strong">${productName}</emphasis> with, ${qty} sales !</speak>`;
                }
                callback(sessionAttributes,
                    buildCustomSpeechletResponse(cardTitle, speechOutput, repromptText, shouldEndSession));

            });
        });
    } else {
        speechOutput = `<speak> ${name}, I dont understand the date given. Please ask me such as : What is my best product since, may 4 2017 or march 2017?</speak>`;
        callback(sessionAttributes,
            buildCustomSpeechletResponse(cardTitle, speechOutput, repromptText, shouldEndSession));
    }
}

/**
 *
 * @param intent
 * @param session
 * @param callback
 * @constructor
 */
function GetNumberOfPendingOrder(intent, session, callback) {
    let name;
    let cardTitle = 'get pending orders';
    let repromptText = null;
    let sessionAttributes = session.attributes;
    let shouldEndSession = false;
    let speechOutput = '';
    name = getNameFromSession(session);
    sessionAttributes.name = name;
    var apiOption;
    getApiOption(function(response){
        apiOption = response;
        var baseUrl = oroApiPath + 'dnd/oro/api/pending/orders.json';
        apiOption.path = baseUrl;
        doCall(apiOption, false, function(response) {
            speechOutput = `${name}, there are ${response.orders} orders in pending, on your oro commerce platform.`;
            callback(sessionAttributes,
                buildSpeechletResponse(cardTitle, speechOutput, repromptText, shouldEndSession));
        });
    });
}

/**
 *
 * @param intent
 * @param session
 * @param callback
 * @constructor
 */
function GetRevenueForWebSiteFrom(intent, session, callback) {
    let name;
    let from;
    let website;
    let fromSlot = intent.slots.date;
    let websiteSlot = intent.slots.website;
    let cardTitle = 'get prevenue for website with date';
    let repromptText = null;
    let sessionAttributes = session.attributes;
    let shouldEndSession = false;
    let speechOutput = '';
    name = getNameFromSession(session);
    sessionAttributes.name = name;
    var apiOption;
    if (fromSlot.value) {
        from = fromSlot.value;
    }
    if (websiteSlot.value) {
        website = websiteSlot.value;
    }

    if (website && from) {
        getApiOption(function(response){
            apiOption = response;
            var baseUrl = oroApiPath + 'dnds/' + website + '/oros/' + from + '/api/revenue/for/web/site/and/date.json';
            apiOption.path = baseUrl;
            doCall(apiOption, false, function(response) {
                from = response.date;
                from = from.replace(/-/g, '');
                var revenues = response.revenue;
                speechOutput = `<speak> ${name}, your revenue for the website of ${website} from <say-as interpret-as="date" format="ymd">${from}</say-as> on your oro commerce platform, is, ${revenues} €</speak>`;
                callback(sessionAttributes,
                    buildCustomSpeechletResponse(cardTitle, speechOutput, repromptText, shouldEndSession));
            });
        });
    }
    if(!website) {
        speechOutput = "I'm not sure which website you picked. Please try again.";
        repromptText = "I'm not sure which website you picked. Please try again.";
    }
    if(!from) {
        speechOutput = "I'm not sure which date you picked. Please try again.";
        repromptText = "I'm not sure which date you picked. Please try again.";
    }
    setTimeout(function(){
        callback(sessionAttributes,
                buildCustomSpeechletResponse(cardTitle, speechOutput, repromptText, shouldEndSession));
    }, 1500);
}

/**
 *
 * @param intent
 * @param session
 * @param callback
 */
function getAvailableShops(intent, session, callback) {
    let name;
    let cardTitle = 'get available shops';
    let repromptText = null;
    let sessionAttributes = session.attributes;
    let shouldEndSession = false;
    let speechOutput = '';
    name = sessionAttributes.name;
    var apiOption;
    getApiOption(function(response){
        apiOption = response;
        var baseUrl = oroApiPath + 'dnd/oro/api/available/shop.json';
        apiOption.path = baseUrl;
        doCall(apiOption, false, function(response) {
            var shops = response.shops;
            let countShops = shops.length;
            let shopList = '';
            for(var i= 0; i<countShops; i++) {
                shopList += shops[i].name + ', ';
            }
            shopList = shopList.substr(0, shopList.length -2);
            speechOutput = `<speak> ${name}, There are : ${shopList}. Of available shop, on your oro commerce platform.</speak>`;
            callback(sessionAttributes,
                buildCustomSpeechletResponse(cardTitle, speechOutput, repromptText, shouldEndSession));
        });
    });    
}

// --------------- Events -----------------------
/**
 * Called when the session starts.
 */
function onSessionStarted(sessionStartedRequest, session) {
    console.log(`onSessionStarted requestId=${sessionStartedRequest.requestId}, sessionId=${session.sessionId}`);
}

/**
 * Called when the user launches the skill without specifying what they want.
 */
function onLaunch(launchRequest, session, callback) {
    console.log(`onLaunch requestId=${launchRequest.requestId}, sessionId=${session.sessionId}`);
    // Dispatch to your skill's launch.
    getWelcomeResponse(callback);
}

/**
 * Called when the user specifies an intent for this skill.
 */
function onIntent(intentRequest, session, callback) {
    console.log(`onIntent requestId=${intentRequest.requestId}, sessionId=${session.sessionId}`);

    let intent = intentRequest.intent;
    let intentName = intentRequest.intent.name;
    // Dispatch to your skill's intent handlers
    switch (intentName) {
        case 'GetSalesSince':
        case 'GetRevenuesFrom':
            GetRevenuesFrom(intent, session, callback);
            break;
        case 'GetRevenues':
            GetRevenues(intent, session, callback);
            break;
        case 'GetRevenueForWebSiteFrom':
            GetRevenueForWebSiteFrom(intent, session, callback);
            break;
        case 'GetNumberOfPendingOrder':
            GetNumberOfPendingOrder(intent, session, callback);
            break;
        case 'getMostSalesProduct':
            getMostSalesProduct(intent, session, callback);
            break;
        case 'getMostSalesProductDate':
            getMostSalesProductDate(intent, session, callback);
            break;
        case 'SetName':
            setNameInSession(intent, session, callback);
            break;
        case 'setShop':
            setShopInSession(intent, session, callback);
            break;
        case 'setMyDate':
            setMyDateInSession(intent, session, callback);
            break;
        case 'SayMyName':
            getName(intent, session, callback);
            break;
        case 'playTheGame':
            playTheGame(intent, session, callback);
            break;            
        case 'getAvailableShop':
            getAvailableShops(intent, session, callback);
            break;
        case 'insertCoin':
            insertCoin(intent, session, callback);
            break;
        case 'getAverageShoppingCart':
            getAverageShoppingCart(intent, session, callback);
            break;
        case 'getTheMostSalesProductStoreDate':
            getTheMostSalesProductStoreDate(intent, session, callback);
            break;
        case 'AMAZON.HelpIntent':
            getWelcomeResponse(callback);
            break;
        case 'AMAZON.StopIntent':
        case 'AMAZON.CancelIntent':
            handleSessionEndRequest(intent, session, callback);
            break;
        default:
            helpMe(session, callback);
    }
}

/**
 *
 * @param session
 * @param callback
 */
function helpMe(session, callback) {
    let cardTitle = 'Help me';
    let repromptText = '';
    let sessionAttributes = session.attributes;
    let shouldEndSession = false;
    let speechOutput = '';
    let name = getNameFromSession(session);
    speechOutput = `<speak> ${name}, I dont understand what you said. Can you repeat please ? </speak>`;
    repromptText = sentence.basicSpeechOutput;

    callback(sessionAttributes,
        buildCustomSpeechletResponse(cardTitle, speechOutput, repromptText, shouldEndSession));
}

/**
 * Called when the user ends the session.
 * Is not called when the skill returns shouldEndSession=true.
 */
function onSessionEnded(sessionEndedRequest, session) {
    console.log(`onSessionEnded requestId=${sessionEndedRequest.requestId}, sessionId=${session.sessionId}`);
    // Add cleanup logic here
}


// --------------- Main handler -----------------------

// Route the incoming request based on type (LaunchRequest, IntentRequest,
// etc.) The JSON body of the request is provided in the event parameter.
exports.handler = (event, context, callback) => {
    try {
        console.log(`event.session.application.applicationId=${event.session.application.applicationId}`);

        /**
         * Uncomment this if statement and populate with your skill's application ID to
         * prevent someone else from configuring a skill that sends requests to this function.
         */
        /*
        if (event.session.application.applicationId !== 'amzn1.echo-sdk-ams.app.[unique-value-here]') {
             callback('Invalid Application ID');
        }
        */

        if (event.session.new) {
            onSessionStarted({
                requestId: event.request.requestId
            }, event.session);
        }

        if (event.request.type === 'LaunchRequest') {
            onLaunch(event.request,
                event.session,
                (sessionAttributes, speechletResponse) => {
                    callback(null, buildResponse(sessionAttributes, speechletResponse));
                });
        } else if (event.request.type === 'IntentRequest') {
            onIntent(event.request,
                event.session,
                (sessionAttributes, speechletResponse) => {
                    callback(null, buildResponse(sessionAttributes, speechletResponse));
                });
        } else if (event.request.type === 'SessionEndedRequest') {
            onSessionEnded(event.request, event.session);
            callback();
        }
    } catch (err) {
        callback(err);
    }
};

/**
* Generat the random number for the game of less or more
*
*/
function generateNumber(callback) {
    var nb =  Math.floor((Math.random() * 100) + 1);
    return callback(nb);
};

/**
 *
 * @param intent
 * @param session
 * @param callback
 */
function playTheGame(intent, session, callback) {
    let sessionAttributes = session.attributes;    
    var gameCpt = sessionAttributes.gameCpt;
    var gameNumber = sessionAttributes.gameNumber;
    let speechOutput ='<speak> This the game of : less or more. Find the number of the day ! It\'s between 1 and 100. You\'ve got 5 try.</speak>';
    let cardTitle ='Play the game';
    let numberSlot = intent.slots.my_number;
    let repromptText = 'This the game of : less or more. Find the secret number of the day. It between 1 and 100. You\'ve got 5 try.';
    var name = getNameFromSession(session);
    if(numberSlot.value){
        numberSlot = numberSlot.value;
        if(numberSlot <101 && numberSlot >= 0) {
            if (gameCpt < 4) {
                gameCpt++;
                if (gameNumber > numberSlot) {
                    speechOutput = `<speak> It's more than ${numberSlot}</speak>`;
                }
                if( gameNumber < numberSlot) {
                    speechOutput = `<speak> It's less than ${numberSlot}</speak>`;
                }
                if( gameNumber == numberSlot) {
                    speechOutput = `<speak> Congratulations, you  find the secret number ! To play again said : "Insert coin"</speak>`;
                }
                sessionAttributes.gameCpt = gameCpt;
            } else if (gameCpt >= 4){
                speechOutput = `<speak> Game over. The number was ${gameNumber}. To play again said : "Insert coin"</speak>`;
            }
        }else{
            speechOutput = `<speak> Sorry, I understand ${numberSlot}, it's not an available value. Pick another number please.</speak>`;
        }
    }
    setTimeout(function(){
        callback(sessionAttributes,
            buildCustomSpeechletResponse(cardTitle, speechOutput, repromptText, false));
    },1500);
}

/**
 *
 * @param intent
 * @param session
 * @param callback
 */
function insertCoin(intent, session, callback) {
    let sessionAttributes = session.attributes;
    let speechOutput ='<speak> Welcome back to find the number of orders. It\'s between 1 and 100. You\'ve got 5 try. </speak>';
    let cardTitle ='Play the game';    
    let repromptText = 'This is the game of : less or more. Find the number past of the day. It\'s between 1 and 100. You\'ve got 5 try.';    
    generateNumber(function(response){
        sessionAttributes.gameNumber = response;
    });
    sessionAttributes.gameCpt = 0;
    setTimeout(function() {
        callback(sessionAttributes,
            buildCustomSpeechletResponse(cardTitle, speechOutput, repromptText, false));
    }, 1500);    
}

/**
 *
 * @param intent
 * @param session
 * @param callback
 */
function getTheMostSalesProductStoreDate(intent, session, callback) {
    let name;
    let dateSlot = intent.slots.date;
    let storeSlot = intent.slots.store;
    let date = false;
    let store = false;
    let cardTitle = 'get best seller product with date and store';
    let repromptText = 'Please ask me such as : Tell me the best seller product since march for default store.';
    let sessionAttributes = session.attributes;
    name = getNameFromSession(session);
    let shouldEndSession = false;
    let speechOutput = `<speak> ${name}, I dont understand the date or store given. Please ask me such as : Tell me the best seller product since march for default store.</speak>`;
    var apiOption;
    sessionAttributes.name = name;
    if (dateSlot && dateSlot.value) {
        date = dateSlot.value;
    }

    if (storeSlot && storeSlot.value) {
        store = storeSlot.value;
    }
    getApiOption(function(response){
        apiOption = response;
        if (date && store != "store" && false !== store) {
            var baseUrl = oroApiPath + 'dnds/'+date+'/oros/'+store+'/api/the/most/sales/product/website/date.json';
            apiOption.path = baseUrl;
            doCall(apiOption, false, function(response) {
                speechOutput = 'There is no best product';
                if (response.products != false) {
                    var sku = response.products.sku;
                    var qty = response.products.qty;
                    var productName = response.products.names;
                    var avg = response.avg;
                    var from = response.date;
                    from = from.replace(/-/g, '');
                    speechOutput = `<speak>${name}, The best seller product since, <say-as interpret-as="date">${from}</say-as> for the ${store} store, on your oro commerce platform, is ${productName} with, ${qty} sales !</speak>`;
                }
                callback(sessionAttributes,
                    buildCustomSpeechletResponse(cardTitle, speechOutput, repromptText, shouldEndSession));
            });
        } 
    });
    setTimeout(function() {
        callback(sessionAttributes,
            buildCustomSpeechletResponse(cardTitle, speechOutput, repromptText, shouldEndSession));
    },1500);    
}

/**
 *
 * @param intent
 * @param session
 * @param callback
 */
function getAverageShoppingCart(intent, session, callback) {
    let name;
    let dateSlot = intent.slots.date;
    let storeSlot = intent.slots.store;
    let date = false;
    let store = false;
    let cardTitle = 'get average shopping cart';
    let repromptText = 'Please ask me such as : Tell me my average customer\'s basket since march';
    let sessionAttributes = session.attributes;
    name = getNameFromSession(session);
    let shouldEndSession = false;
    let speechOutput = `<speak> ${name}, I dont understand the date or store given. Please ask me such as : Tell me my average customer's basket since march.</speak>`;
    var apiOption;
    sessionAttributes.name = name;
    if (dateSlot && dateSlot.value) {
        date = dateSlot.value;
    }

    if (storeSlot && storeSlot.value) {
        store = storeSlot.value;
    }
    getApiOption(function(response){
        apiOption = response;
        if (date && store != "store" && false !== store) {
            var baseUrl = oroApiPath + 'dnds/' + store + '/oros/' + date + '/api/average/shopping/cart/by/website/and/date.json';
            apiOption.path = baseUrl;
            doCall(apiOption, false, function(response) {
                var avg = response.avg;
                var from = response.date;
                from = from.replace(/-/g, '');
                speechOutput = `<speak> ${name}, your average customer's basket since, <say-as interpret-as="date">${from}</say-as> for the ${store} store, on your oro commerce platform, is ${avg} € </speak>`;
                callback(sessionAttributes,
                    buildCustomSpeechletResponse(cardTitle, speechOutput, repromptText, shouldEndSession));
            });
        } 
        if (date && (store == "store" || false == store)) {
            var baseUrl = oroApiPath + 'dnds/' + date + '/oro/api/average/shopping/cart/date.json';
            apiOption.path = baseUrl;
            doCall(apiOption, false, function(response) {
                var avg = response.avg;
                var from = response.date;
                from = from.replace(/-/g, '');
                speechOutput = `<speak> ${name}, your global average customer's basket since, <say-as interpret-as="date">${from}</say-as>, on your oro commerce platform, is ${avg} € </speak>`;
                callback(sessionAttributes,
                    buildCustomSpeechletResponse(cardTitle, speechOutput, repromptText, shouldEndSession));
            });
        }

        if (!date && !store) {
            var baseUrl = oroApiPath + 'dnd/oro/api/average/shopping/cart.json';
            apiOption.path = baseUrl;
            doCall(apiOption, false, function(response) {
                var avg = response.avg;
                speechOutput = `<speak> ${name}, your global average customer's basket since, lifetime, on your oro commerce platform, is ${avg} € </speak>`;
                callback(sessionAttributes,
                    buildCustomSpeechletResponse(cardTitle, speechOutput, repromptText, shouldEndSession));
            });
        }
    });
    
    setTimeout(function() {
        callback(sessionAttributes,
            buildCustomSpeechletResponse(cardTitle, speechOutput, repromptText, shouldEndSession));
    },1500);
}

/**
* Call the web API of Oro Commerce
*
*/
var doCall = function(options, wsse, callback) {
    http.get(options, function(res) {
        var body = '';
        res.on('data', function(data) {
            body += data;
        });
        res.on('end', function() {
            var result = body;
            if (!wsse) {
                result = JSON.parse(result);
            }
            return callback(result);
        });
    }).on('error', function(e) {
        console.log('Error: ' + e);
    });
}


/**
* Get WSSE Token and generate the HEADER to authentificat to the API
*
*/
var getApiOption = function(callback) {
    doCall(wsseHttpOptions, true, function(response) {
        var token = response;
        var apiOption = {
            host: oroHost,
            path: "",
            method: 'GET',
            headers: {
                'Authorization': 'WSSE profile="UsernameToken"',
                'X-WSSE': token,
                'Content-Type': 'application/json'
            }
        };

        return callback(apiOption);
    });
};

/**
 *
 * @param date
 * @param session
 * @param callback
 */
var getRevenueWithWebsiteAndDate = function(date, session, callback) {
    let website = getShopFromSession(session);
    let from = date;
    let speechOutput ='';
    var name = getNameFromSession(session);
    let sessionAttributes = session.attributes;
    sessionAttributes.name = name;    
    let cardTitle = "Get revenues with shop and date";
    var apiOption;    
    getApiOption(function(response){
        apiOption = response;
        var baseUrl = oroApiPath + 'dnds/' + website + '/oros/' + from + '/api/revenue/for/web/site/and/date.json';
        apiOption.path = baseUrl;
        doCall(apiOption, false, function(response) {
            var revenues = response.revenue;
            from = response.date;
            from = from.replace(/-/g, '');
            speechOutput = `<speak>  ${name}, the revenue of the ${website} store since <say-as interpret-as="date">${from}</say-as> on your oro commerce platform is, ${revenues} €</speak>`;
            callback(sessionAttributes,
                buildCustomSpeechletResponse(cardTitle, speechOutput, null, false));
        });
    });
};