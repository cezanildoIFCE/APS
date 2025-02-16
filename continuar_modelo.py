import os
import numpy as np
import tensorflow as tf
from tensorflow.keras.applications import ResNet50
from tensorflow.keras.layers import Dense, GlobalAveragePooling2D, LSTM, TimeDistributed, Dropout, Reshape, Flatten
from tensorflow.keras.models import Model, load_model
from tensorflow.keras.callbacks import ModelCheckpoint, EarlyStopping, ReduceLROnPlateau
from tensorflow.keras.utils import to_categorical
from sklearn.utils import shuffle
from sklearn.metrics import classification_report, confusion_matrix
from PIL import Image
import time
import gc

# Funções para calcular e formatar o tempo
def calcular_tempo(inicio):
    return time.time() - inicio

def formatar_tempo(segundos):
    horas = int(segundos // 3600)
    minutos = int((segundos % 3600) // 60)
    segundos_restantes = segundos % 60
    return f"{horas} horas, {minutos} minutos, {segundos_restantes:.2f} segundos"

# Função para criar o modelo Keras
def criar_modelo():
    base_modelo = ResNet50(weights='imagenet', include_top=False, input_shape=(96, 96, 3))
    for camada in base_modelo.layers:
        camada.trainable = False

    x = base_modelo.output
    x = Flatten()(x)
    x = Dense(7 * 256, activation='relu')(x)
    x = Dropout(0.5)(x)
    x = Reshape((7, 256))(x)
    x = LSTM(128, return_sequences=True)(x)
    x = LSTM(64, return_sequences=True)(x)
    x = TimeDistributed(Dense(36, activation='softmax'))(x)

    modelo = Model(inputs=base_modelo.input, outputs=x)
    modelo.compile(optimizer=tf.keras.optimizers.Adam(learning_rate=0.0001), loss='categorical_crossentropy', metrics=['accuracy'])
    return modelo

# Função customizada para exibir o tempo de cada época durante o treinamento
class TempoCallback(tf.keras.callbacks.Callback):
    def on_epoch_begin(self, epoch, logs=None):
        self.tempo_epoca_inicio = time.time()

    def on_epoch_end(self, epoch, logs=None):
        tempo_epoca_fim = calcular_tempo(self.tempo_epoca_inicio)
        print(f"Tempo da época {epoch + 1}: {formatar_tempo(tempo_epoca_fim)}")

# Começar a contagem de tempo
tempo_inicio = time.time()

# Criar o modelo
modelo = criar_modelo()

# Função para carregar imagens e rótulos
def carregar_dados(arquivos):
    X = []
    y = []
    for arquivo in arquivos:
        # Carregar e redimensionar a imagem
        img = Image.open(arquivo).resize((96, 96))
        img_array = np.array(img)
        X.append(img_array)

        # Extrair o texto da placa a partir do nome do arquivo
        texto_placa = os.path.basename(arquivo).split('_')[0]  # Ex: ABA3664 ou ABC1D23
        rótulo = [ord(c) - ord('A') if c.isalpha() else int(c) + 26 for c in texto_placa]
        y.append(rótulo)
    
    X = np.array(X) / 255.0  # Normalizar os dados
    y = np.array([to_categorical(rótulo, num_classes=36) for rótulo in y])
    return X, y

# Ajustar este caminho para o diretório onde as imagens das placas foram geradas
base_dir = os.path.join(os.path.dirname(__file__), 'placas_artificiais')

# Função para carregar todas as imagens de uma vez (apenas para saber o número de imagens)
def carregar_imagens(diretorio):
    return sum([len(files) for r, d, files in os.walk(diretorio)])

# Carregando os dados **********************************************************************************
numero_imagens_placa = carregar_imagens(base_dir)
print(f"Número de Imagens de Placas: {numero_imagens_placa}")

arquivos = []
for root, dirs, files in os.walk(base_dir):
    for file in files:
        if file.endswith(".png"):  # ou .jpg dependendo do formato
            arquivos.append(os.path.join(root, file))
arquivos = shuffle(arquivos, random_state=42)

# Divisão em treino, validação e teste
split_train = int(0.7 * numero_imagens_placa)
split_val = int(0.9 * numero_imagens_placa)

arquivos_treino = arquivos[:split_train]
arquivos_val = arquivos[split_train:split_val]
arquivos_teste = arquivos[split_val:]

print(f"Conjunto de treino: {len(arquivos_treino)} imagens")
print(f"Conjunto de validação: {len(arquivos_val)} imagens")
print(f"Conjunto de teste: {len(arquivos_teste)} imagens")
print('\n')

gc.collect()

X_treino, y_treino = carregar_dados(arquivos_treino)
X_val, y_val = carregar_dados(arquivos_val)
X_teste, y_teste = carregar_dados(arquivos_teste)
gc.collect()

# Carregar o modelo anterior
modelo_anterior = load_model('modelo_checkpoint.keras')

# Early Stopping e ReduceLROnPlateau
early_stopping = EarlyStopping(monitor='val_loss', patience=5, restore_best_weights=True)
reduce_lr = ReduceLROnPlateau(monitor='val_loss', factor=0.2, patience=3, min_lr=0.00001)
checkpoint = ModelCheckpoint('novo_modelo_checkpoint.keras', monitor='val_loss', save_best_only=True, mode='min')

# Continuar o treinamento
history = modelo_anterior.fit(
    X_treino, y_treino,
    batch_size=32,
    epochs=60,  # Ajuste para o número total de épocas desejadas
    validation_data=(X_val, y_val),
    callbacks=[early_stopping, reduce_lr, checkpoint, TempoCallback()],
    verbose=1
)

# Salvar o novo modelo treinado
modelo_anterior.save('novo_modelo_treinado.keras')

# Avaliação
print("\nAvaliando o novo modelo no conjunto de teste:")
perda_teste, acuracia_teste = modelo_anterior.evaluate(X_teste, y_teste)
print(f"Perda: {perda_teste:.4f}")
print(f"Acurácia: {acuracia_teste:.4f}")

# Relatório de métricas
y_pred = modelo_anterior.predict(X_teste)
y_pred_classes = np.argmax(y_pred, axis=-1)
y_true_classes = np.argmax(y_teste, axis=-1)

# Ajustar o formato para compatibilidade com o classification_report
y_pred_classes_flat = y_pred_classes.flatten()
y_true_classes_flat = y_true_classes.flatten()

print("\nRelatório de classificação:")
print(classification_report(y_true_classes_flat, y_pred_classes_flat))

print("\nMatriz de Confusão:")
print(confusion_matrix(y_true_classes_flat, y_pred_classes_flat))

tempo_total = calcular_tempo(tempo_inicio)
print(f"Tempo total de execução: {tempo_total:.2f} segundos")
print(f"Tempo total de execução: {formatar_tempo(tempo_total)}")
