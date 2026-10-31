import threading
import queue
import time
import random

NOMBRES = ["Candia", "Poma", "Siles", "Figueredo"]


def prueba(numero):
    print(f"\n=== PRUEBA {numero} ===")
    trabajos = queue.Queue()
    resultados = {}
    lock = threading.Lock()

    for doc_id in range(1, 25):
        trabajos.put((doc_id, random.randint(1, 5)))

    def trabajador(nombre):
        docs = []
        while len(docs) < 6:
            try:
                doc_id, tamano = trabajos.get_nowait()
            except queue.Empty:
                break
            time.sleep(tamano * 0.1)
            docs.append(doc_id)
        with lock:
            resultados[nombre] = docs

    hilos = [threading.Thread(target=trabajador, args=(nom,)) for nom in NOMBRES]
    for h in hilos:
        h.start()
    for h in hilos:
        h.join()

    for nom in NOMBRES:
        print(nom, "procesó", len(resultados[nom]), "documentos:", sorted(resultados[nom]))

    total = sum(len(d) for d in resultados.values())
    ids = [i for d in resultados.values() for i in d]
    print("Total procesado:", total)
    print("Verificación 1 (suma = 24):", "OK" if total == 24 else "ERROR")
    print("Verificación 2 (IDs únicos):", "OK" if len(set(ids)) == 24 else "ERROR")
    print("Verificación 3 (hilos terminaron):", "OK" if not any(h.is_alive() for h in hilos) else "ERROR")


for n in range(1, 5):
    prueba(n)