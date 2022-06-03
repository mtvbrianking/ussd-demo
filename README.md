## Demo USSD App

Based on [bmatovu/laravel-ussd](https://github.com/mtvbrianking/laravel-ussd)

### Generic Demo

**CURL**

```bash
curl http://localhost:8000/api/ussd \
  -d 'phone_number=256772100103' \
  -d 'service_code=*384#' \
  -d 'input=*384*1#' \
  -d 'answer=1' \
  -d 'session_id=629a23043b8c7' \
  -d 'network_code=6001'
```

**CLI Simulator**

```bash
./phone --help
./phone 256772100103
./phone 256772100103 --dail "*384*1#"
```

### Africastalking Demo

**Online Simulator**

- [Create USSD App](https://account.africastalking.com/apps/sandbox)
- [USSD Service Code](https://account.africastalking.com/apps/sandbox/ussd/codes)
- [Test number: 256772100103](https://developers.africastalking.com/simulator)

**CURL**

```bash
curl http://localhost:8000/api/ussd/at \
  -d 'phoneNumber=+256772100103' \
  -d 'serviceCode=*384*35711#' \
  -d 'text=' \
  -d 'sessionId=ATUid_6c772a68e52bfc41e8e8d5289db4d90c' \
  -d 'networkCode=99999'
```

**CLI Simulator**

```bash
./africastalking --help
./africastalking 256772100103
./africastalking 256772100103 --dail "*384*35711#"
```
