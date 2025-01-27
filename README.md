> ⚠️ **DISCLAIMER**
>
> I decided to do some more improvements to the codebase post-deadline. It is not meant to be part of the test task, but for the sake of my own satisfaction with the quality of the code. Since I wanted to be transparent with this, **you can access the deadline state in [this tag](https://github.com/gabe777/symbol-mailer/releases/tag/v0.1.0-alpha-deadline-state)**.
> [This branch](https://github.com/gabe777/symbol-mailer/tree/deadline-state-at-2025-01-15-11-00-00-CET) contains almost the same state, except for minor fixes and an added workflow.
> 
> Improvements I am working on post-deadline include (but not necessarily limited to):
>  - Improved long term caching of historical data with monthly segmentation
>  - Making external api calls more failsafe with circuit-breaker
>  - Finishing the RateLimiter, that is a placeholder in the deadline state.
>  - Raising test coverage
> 

# Symbol Historical Data

A test project for XM.

## Prerequisites

1. **Docker**: Install Docker from the [official Docker website](https://docs.docker.com/get-docker/).
2. **ddev**: Install ddev from the [official ddev website](https://ddev.readthedocs.io/en/stable/#installation).

## Installation

1. **Clone the Repository**:
   ```sh
   git clone https://github.com/gabe777/symbol-mailer.git
   cd symbol-mailer
   ```

2. **Start ddev**:
   Ensure you are in the project directory and run:
   ```sh
   ddev start
   ```
   You may get an error in case there is a **redis instance already running** on the default port (*6379*). In this case modifying the port number in ```.ddev/docker-compose.redis.yaml``` and run ```ddev start``` again should solve the issue. 
   ```yaml
   # .ddev/docker-compose.redis.yaml
   services:
     redis:
       ...
       ports:
         - "ChangeThis:6379"
   ```

3. **Install Dependencies**:
   Within the project root directory, run:
   ```sh
   ddev composer install
   ```

[//]: # ()
[//]: # (3. **Run Migrations**:)

[//]: # (   Within the project root directory, run:)

[//]: # (   ```sh)

[//]: # (   ddev php bin/console doctrine:migrations:migrate)

[//]: # (   ```)
   
4. **Run Tests**: Make sure that unit and integration tests pass:
   ```shell
   ddev php bin/phpunit
   ```
   If you want coverage data, you have to enable xdebug first:
   ```shell
   ddev exec xdebug
   ddev php bin/phpunit --coverage-html coverage/
   ```

5. **Run the Application**:
   Access the application at:
   
   > [https://symbol-mailer.ddev.site](https://symbol-mailer.ddev.site)

   You should see the default Symfony 7 page for now.

   
6. **Start Message queue processing**:
Needed for running background Jobs, eg. csv generation and email sending:
   ```shell
   ddev php bin/console messenger:consume async -vv
   ```
   You may omit -vv in case no need for verbose logging to console (eg. in production environment)


7. **Please visit the API Docs**:
   You will be able to try the ```/history``` endpoint after setting up an api key in ```.env``` *API_KEYS* key, and set the same value for Authorize on the swagger ui. 

   > [https://symbol-mailer.ddev.site/api/doc](https://symbol-mailer.ddev.site/api/doc)



   

## Configuration

Ensure your `.env` file contains the following configurations with associated values:
```
RAPIDAPI_KEY=
API_KEYS=
```

## API Endpoints

### Fetch historical data for a given Symbol

- **Endpoint**: `/api/v1/stock/{companySymbol}/history`
- **Method**: `POST`

See at the [API Docs](https://symbol-mailer.ddev.site/api/docs) in details.

#### How does the endpoint work?

The `/history` endpoint does 2 things from a user perspective

1. Json result 

   Retrieves and returns a list of daily **OHLCV** data of a Symbol for a given period in `json` format, based on the request payload. This is a synchronous process, and uses request cache to save resources.

2. Email sending
 
   Sends an email to the user with the same **OHLCV** data attached in *CSV* format. This is an async process, and does not add to the response time of the api request.
   
### Authorization

The API requires an **API key** in order to authorize the request. In this implementation there is no identity related to the api key, so it does not really authenticate, but without a valid api key the request will return with a `401 Unauthorized` HTTP code. 

The API key can be sent either in the `Authorization` request header - preferred way -, or as a get parameter named `api_key`. 

#### Authorization config

It is possible to set multiple api keys for the application. To do that just set a _comma separated string_ as the value of `API_KEYS` environment variable in the appropriate **.env*** file.
Example
```shell
API_KEYS=1231231233,abcabcabc
```

## External APIs

The application uses external APIs for **fetching the list of valid Symbols** and for **fetching the historical data**.
Both processes are implemented a way to make change of the used External API as simple as implementing the Client class for them and set the DI in services.yaml.


## Considerations

### Database as persistent storage

Due to the fact that any instance of data fetched from the api is historic and not subject to change, it would be beneficial to use a relational or nosql database for permanent storage at least for the Historical Data.
From a performance perspective it could still be fine with the cache layer in front of it, but the data persistence wouldn't be in the hands of the cache provider. Also refreshing the cache from a relational database where possible as opposed to the YF-API theoretically more efficient looking at the maximum volume of data to be expected.

In my view a table containing the daily OHLCV data (with the fields symbol, day, open, high, low, close, volume) would be sufficient due to the fact that according to my educated guess one row would not be greater than 100 Bytes as an average. This way 100 years of historical data for a given symbol consumes 3,65 MB. At this volume even retrieving the total history for a given symbol wouldn't result in serious traffic and memory load. 
The max theoretical size of data - without indices - would be slightly less than 11GB which seems manageable, and it would mean 100 years of history for each of the less than 3000 symbols. 

### Rate Limiter

As an extra layer of protection for the API the implementation of a rate limiter mechanism would be beneficial. Actually it has its place to live in `App\Service\RateLimiterService`, but the implementation is not complete due to the lack of time.  

### More complex authorization method
Though no sensitive data is transferred to the client, it might be beneficial to implement an OAuth based authentication method for the API, especially if plans are to improve the API with further, more sensitive endpoints. 
