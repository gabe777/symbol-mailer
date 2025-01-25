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
Needed for running background data fetching Jobs:
   ```shell
   ddev php bin/console messenger:consume async -vv
   ```
   You may omit -vv in case no need for verbose logging to console (eg. in production environment)


7. **Please visit the API Docs**:
   You will be able to try the ```/history``` endpoint after setting up an api key in ```.env``` *API_KEYS* key, and set the same value for Authorize on the swagger ui. 

   > [https://symbol-mailer.ddev.site/api/docs](https://symbol-mailer.ddev.site/api/docs)



   

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

1. Retrieves and returns a list of daily **OHLCV** data of a Symbol for a given period in `json` format, based on the request payload.
2. Sends an email to the user with the same **OHLCV** data attached in *CSV* format.


## Considerations

### Database as persistent storage

Due to the fact that any instance of data fetched from the api is historic and not subject to change, it would be beneficial to use a relational or nosql database for permanent storage at least for the Historical Data.
From a performance perspective it could still be fine with the cache layer in front of it, but the data persistence wouldn't be in the hands of the cache provider. Also refreshing the cache from a relational database where possible as opposed to the YF-API theoretically more efficient looking at the maximum volume of data to be expected.

In my view a table containing the daily OHLCV data (with the fields symbol, day, open, high, low, close, volume) would be sufficient due to the fact that according to my educated guess one row would not be greater than 100 Bytes as an average. This way 100 years of historical data for a given symbol consumes 3,65 MB. At this volume even retrieving the total history for a given symbol wouldn't result in serious traffic and memory load. 
The max theoretical size of data - without indices - would be slightly less than 11GB which seems manageable, and it would mean 100 years of history for each of the less than 3000 symbols. 

### Rate Limiter

As an extra layer of protection for the API the implementation of a rate limiter mechanism would be beneficial. Actually it has its place to live in `App\Service\RateLimiterService`, but the implementation is not complete due to the lack of time.  


