Demo USSD App
---

Tailored to..
- Africastalking
- Sparor's USSD package

**Sample HTTP Log**

You may test via Postman or any other CURL client of your choice

```bash
curl http://localhost:8000/api \
  -d 'phoneNumber=+256772100104' \
  -d 'serviceCode=*384*35711#' \
  -d 'text=' \
  -d 'sessionId=ATUid_6c772a68e52bfc41e8e8d5289db4d90c' \
  -d 'networkCode=99999'
```

```bash
[2022-01-30 15:07:54] local.INFO: HTTP_LOG_ExeHVbBxX6 [REQUEST] POST /api HTTP/1.1  
[2022-01-30 15:07:54] local.DEBUG: HTTP_LOG_ExeHVbBxX6 [REQUEST] [Headers]
Accept-Encoding:   gzip
Content-Length:    130
Content-Type:      application/x-www-form-urlencoded
Host:              a0c2-41-210-146-88.ngrok.io
User-Agent:        at-ussd-api/1.0
X-Forwarded-For:   164.177.141.82
X-Forwarded-Proto: https
  
[2022-01-30 15:07:54] local.DEBUG: HTTP_LOG_ExeHVbBxX6 [REQUEST] [Body]
phoneNumber=%2B256772100104&serviceCode=%2A384%2A35711%23&text=&sessionId=ATUid_6c772a68e52bfc41e8e8d5289db4d90c&networkCode=99999
[2022-01-30 15:07:54] local.INFO: HTTP_LOG_ExeHVbBxX6 [RESPONSE] HTTP/1.1 200 OK 1.00s  
[2022-01-30 15:07:54] local.DEBUG: HTTP_LOG_ExeHVbBxX6 [RESPONSE] [Headers]
Cache-Control:         no-cache, private
Content-Type:          text/html; charset=UTF-8
Date:                  Sun, 30 Jan 2022 15:07:54 GMT
X-Ratelimit-Limit:     60
X-Ratelimit-Remaining: 59
X-Request-Id:          ExeHVbBxX6
  
[2022-01-30 15:07:54] local.DEBUG: HTTP_LOG_ExeHVbBxX6 [RESPONSE] [Body]
CON DummySACCO
1.Savings
2.Loans
3.Exit
```

```bash
./phone --help
./phone 0786352836
./phone 0786352836 --dail *308#
./phone 0786352836 --dail *308*1*2#

curl -i http://localhost:8000/api \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -d '{"session_id":"10050","answer":""}'

curl -i http://localhost:8000/api \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -d '{"session_id":"10050","answer":"jdoe"}'
````