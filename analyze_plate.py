import sys
import os
import numpy as np
import tensorflow as tf
from tensorflow.keras.applications import ResNet50
from tensorflow.keras.layers import Dense, LSTM, TimeDistributed, Dropout, Reshape, Flatten
from tensorflow.keras.models import Model, load_model
from PIL import Image

# Função para carregar a imagem
def load_image(image_path):
    img = Image.open(image_path)
    img = img.resize((96, 96))  # Redimensionar para o tamanho esperado pelo modelo
    img_array = np.array(img)
    img_array = np.expand_dims(img_array, axis=0)  # Adicionar dimensão de lote
    img_array = img_array / 255.0  # Normalizar os dados
    return img_array

# Função para criar o modelo Keras (deve ser consistente com o modelo treinado)
def criar_modelo():
    base_modelo = ResNet50(weights='imagenet', include_top=False, input_shape=(96, 96, 3))
    for camada in base_modelo.layers:
        camada.trainable = False

    x = base_modelo.output
    x = Flatten()(x)
    x = Dense(7 * 256, activation='relu')(x)  # Prever uma sequência de 7 caracteres com 256 dimensões cada
    x = Dropout(0.5)(x)
    x = Reshape((7, 256))(x)
    x = LSTM(128, return_sequences=True)(x)
    x = LSTM(64, return_sequences=True)(x)
    x = TimeDistributed(Dense(36, activation='softmax'))(x)

    modelo = Model(inputs=base_modelo.input, outputs=x)
    modelo.compile(optimizer=tf.keras.optimizers.Adam(learning_rate=0.0001), loss='categorical_crossentropy', metrics=['accuracy'])
    return modelo

def main():
    if len(sys.argv) != 2:
        print("Uso: python analyze_plate.py <caminho_da_imagem>")
        return
    
    image_path = sys.argv[1]

    try:
        # Carregar a imagem
        image = load_image(image_path)

        # Carregar o modelo treinado
        model = load_model('modelo_treinado.keras')
        
        # Fazer a previsão da placa
        prediction = model.predict(image)
        
        # Converter a previsão em caracteres
        characters = 'ABCDEFGHIJKLMNPQRSTUVWXYZ0123456789'  # Excluir 'O'
        plate = ''.join([characters[np.argmax(pred)] for pred in prediction[0]])
        
        # Imprimir a placa para que o PHP possa capturar a saída
        print(plate)
        
    except Exception as e:
        print(f"Erro: {e}")

if __name__ == "__main__":
    main()
