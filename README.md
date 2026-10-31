# Práctica  (Sistemas Operativos)

El programa tiene una cola con 24 documentos (cada uno con un ID del 1 al 24 y un tamaño al azar de 1 a 5) y 4 trabajadores que los procesan al mismo tiempo. Se hacen 4 pruebas y en cada una cada trabajador procesa 6 documentos.

## Interpretación de cada trabajador

Cada trabajador es un hilo (`Thread`) que ejecuta la misma función `trabajador(nombre)`solo cambia el nombre. Los 4 hacen lo mismo:

- **Candia, Poma, Siles y Figueredo** sacan un documento de la cola con `get_nowait()`. Si la cola está vacía, atrapan `queue.Empty` y terminan.
- Simulan el trabajo con `time.sleep(tamaño * 0.1)`, así un documento más grande tarda más.
- Cada uno se detiene al llegar a 6 documentos procesados.
- Al terminar guardan su lista de documentos en el diccionario compartido `resultados`, dentro de `with lock:`.

Como los tamaños son al azar y los hilos avanzan a distinta velocidad, en cada prueba cambia qué documentos le tocan a cada uno, pero siempre son 6 por trabajador y 24 en total. Los documentos 1 a 4 siempre quedan para Candia, Poma, Siles y Figueredo (en ese orden), porque es el orden en que se inician los hilos y cada uno toma el primer documento disponible al arrancar.

## Preguntas

**¿Por qué queue.Queue es apropiada para compartir trabajos?**
Porque entrega cada documento a un solo trabajador al sacarlo de la cola ya no está para los demás, así que nadie procesa el mismo documento dos veces. Además es segura entre hilos (thread-safe), así que no hace falta proteger a mano los `put` y `get`. Con `get_nowait()` el hilo no se queda esperando si la cola está vacía, lanza `Empty` y el trabajador termina de forma controlada. No se usa `q.empty()` como única condición de salida porque la cola puede cambiar entre la comprobación y la lectura.

**¿Qué protege Lock y qué pasaría si se actualizara el resumen sin él?**
El Lock protege el diccionario compartido `resultados` solo un hilo a la vez puede modificarlo. Sin Lock habría una condición de carrera dos hilos podrían leer y escribir al mismo tiempo y uno pisaría el cambio del otro, así que se podrían perder resultados o quedar un resumen incorrecto (por ejemplo, que la suma no dé 24).

**¿Qué cambiarías si cada documento tuviera que guardarse en un archivo real?**
Cada trabajador guardaría cada documento en un archivo con nombre único (por ejemplo `doc_<id>.txt`) para que dos hilos no escriban el mismo archivo. Si varios hilos escribieran en un mismo archivo (como un log), usaría un Lock para esa escritura. También manejaría errores de lectura y escritura con `try/except` y cerraría los archivos con `with open(...)`
