## Demo USSD App

Based on [Africastalking](https://africastalking.com)

### CURL

```bash
curl http://localhost:8000/api \
  -d 'phoneNumber=+256772100103' \
  -d 'serviceCode=*214#' \
  -d 'text=' \
  -d 'sessionId=ATUid_6c772a68e52bfc41e8e8d5289db4d90c' \
  -d 'networkCode=99999'
```

### Local Simulator

```bash
./africastalking --help
./africastalking "0772100103"
./africastalking "0772100103" --dail "*214#"
```

### AfricasTalking Simulator

Create USSD App

https://account.africastalking.com/apps/sandbox

USSD Service Code: 

https://account.africastalking.com/apps/sandbox/ussd/codes

Test number: 256772100103

https://developers.africastalking.com/simulator
