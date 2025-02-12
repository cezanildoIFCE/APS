#include <WiFi.h>
#include <esp_now.h>
#include <HTTPClient.h>
#include <Wire.h>
#include <LiquidCrystal_I2C.h>
#include <ESP32Servo.h>
#include <SoftwareSerial.h>
#include <ESP32Ping.h>

// Definições de pinos
#define LED 2   //PLACAESP32  
#define LED1 27   //AZUL
#define LED2 26   //VERDE
#define LED3 25 //VERMELHO   
#define SENSOR 18 
#define BOT 32    
#define MOTOR 19  
#define BOT1 12   
#define BOT2 13   


LiquidCrystal_I2C lcd(0x27, 16, 2);
SoftwareSerial RFID(5, 4); // RX, TX
Servo servoMotor;


const char *ssid = "NINOnet";
const char *password = "02081978";
const char *server = "http://192.168.1.180/esp32_remoto.php";
uint8_t macDoReceptor[] = {0xA8, 0x42, 0xE3, 0xA8, 0xD3, 0x90}; //MAC do receptor

String CardLeitor;
String ultimoCartao = ""; 
unsigned long ultimaLeitura = 0; 
const unsigned long intervaloLeitura = 10000; 
int ativada = 0;

void enviarCartaoESPNow(const uint8_t *macAddress, String cardNumber);
void inicializarEspNow(int canalWiFi);
boolean Banco(String CardLeitor, const char *server);
boolean Acionamento(boolean acesso);
void pisca(int A, int T);
void Motor(boolean abre);
void Display(String imp, int col, int lin);
void Display(String imp);
String leitorRfid();
void reconectarWiFi();
boolean testarHTTP(const char *host);

//armazenar o cartão
typedef struct struct_message {
  char cardNumber[11];
} struct_message;
struct_message outgoingMessage;

void setup() {
  Serial.begin(9600);
  RFID.begin(9600);
  
  pinMode(LED, OUTPUT);
  pinMode(LED1, OUTPUT);
  pinMode(LED2, OUTPUT);
  pinMode(LED3, OUTPUT);
  pinMode(SENSOR, INPUT);
  pinMode(BOT, INPUT);
  pinMode(BOT1, INPUT);
  pinMode(BOT2, INPUT);
  servoMotor.attach(MOTOR);
  
  if (digitalRead(SENSOR)) { 
    Motor(false); 
  }

  lcd.init();
  lcd.backlight();

  reconectarWiFi();
  int canalWiFi = WiFi.channel();
  Serial.printf("Canal Wi-Fi conectado: %d\n", canalWiFi);
  inicializarEspNow(canalWiFi);; 
  while (!testarHTTP(server));
  pisca(LED2, 2000);
}

void loop() {
  if (digitalRead(BOT)) {
    digitalWrite(LED3, HIGH);
    Motor(true);
    Serial.println("*******EMERGÊNCIA!!*******");
    Display("**EMERGENCIA!!**");
    while (true) {
      pisca(LED3, 1000);
    }
  }

  while (!digitalRead(SENSOR)) {
    pisca(LED3, 1000);
    lcd.clear();
    lcd.setCursor(0, 0);
    lcd.print("ATENCAO!");
    lcd.setCursor(0, 1);
    lcd.print("CAMINHO OBSTRUIDO!!");
  }
  
  if (RFID.available() > 0 && ativada == 0) {
    Serial.println("Lendo cartão...");
    Display("lendo...");
    CardLeitor = leitorRfid();
    ativada++;;

    if (CardLeitor != "" && (CardLeitor != ultimoCartao || millis() - ultimaLeitura > intervaloLeitura)) {

      boolean acesso = Banco(CardLeitor, server);
      if(acesso){
        enviarCartaoESPNow(macDoReceptor, CardLeitor);
      }
      acesso = Acionamento(acesso);
      if(!acesso){
        ultimoCartao = CardLeitor; 
        ultimaLeitura = millis();
      }
    } else if (CardLeitor == ultimoCartao) {
      pisca(LED3, 1000);
      Serial.println("Cartão já processado recentemente.");
      ultimaLeitura = millis();
    } else {
      Serial.println("Erro ao ler o cartão RFID");
    }
    CardLeitor = "";
  }

  if(ativada > 0){
    RFID.flush();
    ativada++;
  }

  if (digitalRead(BOT1)) {
    Acionamento(true);
  }
  Serial.println("Aguardando...");
  Display("Aguardando...");
  pisca(LED1, 10);
  delay(2000);
  if(ativada > 2){
    ativada = 0;
  }
}


// --------------------------------------------------------------------------------
// Funções auxiliares
boolean Banco(String CardLeitor, const char *server) {
  String CardBanco;
  String postData = "numero_cartao=" + CardLeitor;

  if (WiFi.status() == WL_CONNECTED) {
    HTTPClient http;
    http.begin(server);
    http.addHeader("Content-Type", "application/x-www-form-urlencoded");
    int httpResponseCode = http.POST(postData);
    if (httpResponseCode > 0) {
      String response = http.getString();
      Serial.print("Resposta do servidor: ");
      Serial.println(response);

      if (response == "true") {
        http.end();
        return true;
      } else {
        http.end();
        return false; 
      }
    } else {
      Serial.print("Erro HTTP: ");
      Serial.println(httpResponseCode);
    }
    http.end();
  } else {
    Serial.println("WiFi desconectado!");
  }
  return false;
}

void Display(String imp) {
  lcd.clear();
  lcd.print(imp);
}

void Display(String imp, int col, int lin) {
  lcd.setCursor(col, lin);
  lcd.print(imp);
}

void Motor(boolean abre) {
  if (abre) {
    servoMotor.write(90);
    Serial.println("Catraca aberta");
  } else {
    servoMotor.write(0);
    Serial.println("Catraca fechada");
  }
  delay(3000);
}

void pisca(int A, int T) {
  digitalWrite(A, HIGH);
  delay(T);
  digitalWrite(A, LOW);
  delay(T);
}

String leitorRfid() {
  int cont = 0;
  String id = "";
  while (RFID.available() > 0 && cont < 12) {
    char caractere = RFID.read();
    digitalWrite(LED1, HIGH);
    digitalWrite(LED, HIGH);
    id += caractere;
    cont ++;
    delay(50);
    digitalWrite(LED1, LOW);
    digitalWrite(LED, LOW);
  }
  id = id.substring(1, 11);
  RFID.flush();
  Display("lendo....");
  Serial.print("lendo....");
  return id;
}

boolean Acionamento_1(boolean acesso) {
  if (acesso) {
    Serial.println("Acesso permitido");
    Display("Acesso Permitido");
    pisca(LED2, 1000);
    Motor(true);
    delay(5000);
    Motor(false);
  } else {
    Serial.println("Acesso negado");
    Display("Acesso Negado");
    pisca(LED3, 2000);
  }
  return false;
}

boolean Acionamento(boolean acesso)
{
  if (acesso)
  { 
    Serial.println("Acesso Permitido");
    Display("Acesso Permitido");
    pisca(LED2, 1000);
    if (!digitalRead(SENSOR))
    {
      while (!digitalRead(SENSOR)){
         Serial.println("Atencao! catraca obstruida!");
         Display("Atencao!!");
         Display("libere a passagem", 0, 1);
         pisca(LED3, 2000);
      }
      acesso = true;
    }
    else
    {                           
      digitalWrite(LED1, HIGH); 
      Serial.println("Catraca abrindo..");
      Display("Catraca abrindo..");
      Motor(true);
      int espera = 0;
      while (digitalRead(SENSOR) && espera < 20)
      { 
        espera++;
        Serial.println("Acesso liberado!!");
        Display("Acesso liberado!!");
        if (espera > 10)
        {
          digitalWrite(LED1, LOW);
          pisca(LED3, 500);
          Display("     ATENCAO!");
          Display("   VAI FECHAR!!", 0, 1);
          if (!digitalRead(SENSOR))
          {
            digitalWrite(LED1, HIGH);
          }
        }
        delay(500);
      }
      while (!digitalRead(SENSOR))
      { 
        Serial.println("Complete a passagem!!");
        Display("Complete a passagem!!");
        delay(500); 
      }
      digitalWrite(LED1, LOW);
      Serial.println("Catraca fechando..");
      Display("Catraca fechando..");
      Motor(false);
      if(espera == 20){
        acesso = true;
      }else{
        acesso = false;
      }
    }
  }
  else
  { 
    Serial.println("Acesso Negado");
    Display("Acesso Negado");
    pisca(LED3, 2000);
  }
  return acesso;
}

void reconectarWiFi() {
  WiFi.begin(ssid, password);
  while (WiFi.status() != WL_CONNECTED) {
    Serial.println("Conectando ao WiFi...");
    digitalWrite(LED1, HIGH);
    lcd.clear();
    lcd.println("Conectando WiFi...");
    delay(1000);
  }
  digitalWrite(LED1, LOW);
  digitalWrite(LED2, HIGH);
  Serial.println("Conectado ao WiFi!");
  lcd.clear();
  lcd.println("Conectado WiFi!");
  Serial.print("IP do ESP32: ");
  Serial.println(WiFi.localIP());
  Serial.print("MAC do ESP32: ");
  Serial.println(WiFi.macAddress());
  lcd.clear();
  lcd.println(WiFi.localIP());
  delay(2000);
  lcd.clear();
  Display(WiFi.macAddress());
  Display(WiFi.macAddress().substring(16),0,1);
  lcd.setCursor(0, 1);
  delay(2000);
  digitalWrite(LED2, LOW);
}

boolean testarHTTP(const char *server) {
  Serial.println("Conectando ao servidor via HTTP...");
  lcd.clear();
  lcd.println("Conect servidor HTTP...");
  unsigned long startTime, elapsedTime;
  int successCount = 0;
  for (int i = 0; i < 4; i++) {
    HTTPClient http;
    startTime = millis();
    http.begin(server);
    int httpCode = http.GET();
    elapsedTime = millis() - startTime;
    if (httpCode > 0) {
      successCount++;
      Serial.printf(
        "Resposta de %s: Código=%d, bytes=32, tempo=%lums, TTL=128\n",
        server, httpCode, elapsedTime
      );
      digitalWrite(LED1, HIGH);
      lcd.clear();
      String linha1 = "HTTP: " + String(httpCode);
      String linha2 = "Time: " + String(elapsedTime) + "ms";
      lcd.setCursor(0, 0);
      lcd.print(linha1.substring(0, 16));
      lcd.setCursor(0, 1);
      lcd.print(linha2.substring(0, 16));
      digitalWrite(LED1, LOW);
    } else {
      Serial.printf("Falha na conexão com o servidor: %s\n", http.errorToString(httpCode).c_str());
      String errorMessage = "Erro: " + http.errorToString(httpCode);
      digitalWrite(LED3, HIGH);
      lcd.clear();
      lcd.setCursor(0, 0);
      lcd.print("Falhano servidor:");
      lcd.setCursor(0, 1);
      lcd.print(errorMessage);
      delay(5000);
      digitalWrite(LED3, LOW);
      return false;
    }
    http.end();
    delay(1000);
  }
  return true;
}

void inicializarEspNow(int canal) {
  if (esp_now_init() != ESP_OK) {
    Serial.println("Erro ao inicializar ESP-NOW");
    return;
  }
  esp_now_register_send_cb([](const uint8_t *macAddr, esp_now_send_status_t status) {
    if (status == ESP_NOW_SEND_SUCCESS) {
      Serial.println("Mensagem enviada com sucesso.");
      lcd.clear();
       delay(100);
      lcd.print("Receptor ok");
      pisca(LED2, 1000);
      lcd.clear();
    } else {
      Serial.println("Falha no envio ao receptor.");
      lcd.clear();
       delay(100);
      lcd.print("falha no receptor");
      pisca(LED3, 1000);
      lcd.clear();
    }
  });
  esp_now_peer_info_t peerInfo;
  memcpy(peerInfo.peer_addr, macDoReceptor, 6);
  peerInfo.channel = canal;  // Canal do Wi-Fi
  peerInfo.encrypt = false;

  if (esp_now_add_peer(&peerInfo) != ESP_OK) {
    Serial.println("Erro ao adicionar o peer");
    return;
  }
  Serial.println("ESP-NOW inicializado com sucesso!");
  const char *testMessage = "Teste de conexão";
  esp_err_t result = esp_now_send(macDoReceptor, (uint8_t *)testMessage, strlen(testMessage) + 1);
  if (result == ESP_OK) {
    Serial.println("Teste de conexão com receptor bem-sucedido.");
  } else {
    Serial.printf("Erro no teste de conexão ESP-NOW: %d\n", result);
  }
  lcd.clear();
}

void enviarCartaoESPNow(const uint8_t *macAddress, String cardNumber) {
  cardNumber.toCharArray(outgoingMessage.cardNumber, sizeof(outgoingMessage.cardNumber));
  esp_err_t result = esp_now_send(macAddress, (uint8_t *)&outgoingMessage, sizeof(outgoingMessage));
  if (result == ESP_OK) {
    Serial.println("Cartão enviado com sucesso!");
    lcd.clear();
    delay(200);
    lcd.print("Cartao enviado");
    pisca(LED2, 1000);
    lcd.clear();
  } else {
    Serial.printf("Erro ao enviar cartão via ESP-NOW: %d\n", result);
    lcd.clear();
    delay(200);
    lcd.print("Cartao falhou");
    pisca(LED3, 2000);
    lcd.clear();
  }
  lcd.clear();
  delay(100);
} 