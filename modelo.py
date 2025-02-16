import os
import numpy as np
import tensorflow as tf
from tensorflow.keras.applications import ResNet50
from tensorflow.keras.layers import Dense, GlobalAveragePooling2D, LSTM, TimeDistributed, Dropout, Reshape, Flatten
from tensorflow.keras.models import Model
from tensorflow.keras.callbacks import ModelCheckpoint, EarlyStopping, ReduceLROnPlateau
from tensorflow.keras.regularizers import l2
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

# Função para construir o modelo Keras

def criar_modelo():
    base_modelo = ResNet50(weights='imagenet', include_top=False, input_shape=(96, 96, 3))
    for camada in base_modelo.layers:
        camada.trainable = False

    x = base_modelo.output
    x = Flatten()(x)  # Achatar a saída da camada convolucional
    x = Dense(7 * 256, activation='relu')(x)  # Prever uma sequência de 7 caracteres com 256 dimensões cada
    x = Dropout(0.5)(x)
    
    # Ajuste das dimensões para prever sequência completa de 7 caracteres
    x = Reshape((7, 256))(x)  # Corrigir dimensão para (7, 256)
    x = LSTM(128, return_sequences=True)(x)
    x = LSTM(64, return_sequences=True)(x)
    x = TimeDistributed(Dense(36, activation='softmax'))(x)  # 10 dígitos + 26 letras = 36 classes possíveis

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

# Lendo dados *******************************************************************************************
tempo_etapa_1_inicio = time.time()

# Ajuste este caminho para o diretório onde as imagens das placas foram geradas
base_dir = os.path.join(os.path.dirname(__file__), 'placas_artificiais')

# Função para carregar todas as imagens de uma vez (apenas para saber o número de imagens)
def carregar_imagens(diretorio):
    return sum([len(files) for r, d, files in os.walk(diretorio)])

# Carregando os dados **********************************************************************************
numero_imagens_placa = carregar_imagens(base_dir)
print(f"Número de Imagens de Placas: {numero_imagens_placa}")

tempo_etapa_1_fim = calcular_tempo(tempo_etapa_1_inicio)
print(f"Tempo de Coleta de dados: {formatar_tempo(tempo_etapa_1_fim)}")

# Embaralhar os dados *********************************************************************************
tempo_etapa_2_inicio = time.time()

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

tempo_etapa_2_fim = calcular_tempo(tempo_etapa_2_inicio)
print(f"Tempo de preparação: {formatar_tempo(tempo_etapa_2_fim)}")
gc.collect()

# Carregar e preparar os dados
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


X_treino, y_treino = carregar_dados(arquivos_treino)
X_val, y_val = carregar_dados(arquivos_val)
X_teste, y_teste = carregar_dados(arquivos_teste)
gc.collect()

# Modelo ***********************************************************************************************
tempo_etapa_3_inicio = time.time()

modelo = criar_modelo()

tempo_etapa_3_fim = calcular_tempo(tempo_etapa_3_inicio)
print(f"Tempo de construção do modelo: {formatar_tempo(tempo_etapa_3_fim)}")

# Aumentação de Dados
tempo_etapa_4_inicio = time.time()

datagen = tf.keras.preprocessing.image.ImageDataGenerator(
    rotation_range=20,
    width_shift_range=0.2,
    height_shift_range=0.2,
    shear_range=0.2,
    zoom_range=0.2,
    horizontal_flip=True,
    fill_mode='nearest'
)

# Medir tempo para gerar um lote de dados aumentados
tempo_aumentacao_inicio = time.time()

# Gerar um lote de dados aumentados
for batch in datagen.flow(X_treino, y_treino, batch_size=32):
    break  # Gerar apenas um lote para medição de tempo

tempo_aumentacao_fim = calcular_tempo(tempo_aumentacao_inicio)
print(f"Tempo de geração de um lote de dados aumentados: {formatar_tempo(tempo_aumentacao_fim)}")

tempo_etapa_4_fim = calcular_tempo(tempo_etapa_4_inicio)
print(f"Tempo de Aumentação de Dados: {formatar_tempo(tempo_etapa_4_fim)}")

# Early Stopping e ReduceLROnPlateau
early_stopping = EarlyStopping(monitor='val_loss', patience=5, restore_best_weights=True)
reduce_lr = ReduceLROnPlateau(monitor='val_loss', factor=0.2, patience=3, min_lr=0.00001)
checkpoint = ModelCheckpoint('modelo_checkpoint.keras', monitor='val_loss', save_best_only=True, mode='min')

# Treinamento
tempo_etapa_5_inicio = time.time()

steps_per_epoch = len(X_treino) // 32  # Número de steps por época ajustado

train_dataset = tf.data.Dataset.from_tensor_slices((X_treino, y_treino)).repeat().batch(16)
val_dataset = tf.data.Dataset.from_tensor_slices((X_val, y_val)).repeat().batch(16)

# Treinamento
tempo_etapa_5_inicio = time.time()

steps_per_epoch = len(X_treino) // 32  # Número de steps por época ajustado

train_dataset = tf.data.Dataset.from_tensor_slices((X_treino, y_treino)).repeat().batch(16)
val_dataset = tf.data.Dataset.from_tensor_slices((X_val, y_val)).repeat().batch(16)

history = modelo.fit(
    train_dataset,
    steps_per_epoch=steps_per_epoch,
    epochs=60,
    validation_data=val_dataset,
    validation_steps=len(X_val) // 32,
    callbacks=[early_stopping, reduce_lr, checkpoint, TempoCallback()],
    verbose=1  # Mostrar progresso por época
)

tempo_etapa_5_fim = calcular_tempo(tempo_etapa_5_inicio)
gc.collect()
print(f"Tempo de treinamento: {formatar_tempo(tempo_etapa_5_fim)}")

# Salvar o modelo treinado
tempo_etapa_6_inicio = time.time()

modelo.save('modelo_treinado.keras')

tempo_etapa_6_fim = calcular_tempo(tempo_etapa_6_inicio)
gc.collect()
print(f"Tempo de salvamento do modelo: {formatar_tempo(tempo_etapa_6_fim)}")

# Avaliação
tempo_etapa_7_inicio = time.time()

print("\nAvaliando o modelo no conjunto de teste:")
perda_teste, acuracia_teste = modelo.evaluate(X_teste, y_teste)
print(f"Perda: {perda_teste:.4f}")
print(f"Acurácia: {acuracia_teste:.4f}")

# Relatório de métricas
y_pred = modelo.predict(X_teste)
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

