// LinkedList.java
public class LinkedList {
    private Node head; // Dummy head node
    private int listSize; // Size of the list

    public LinkedList() {
        head = new Node(null); // Initialize head
        listSize = 0; // Set initial size to 0
    }

    public void add(Object data, int index) {
        if (index < 0 || index > listSize) {
            throw new IndexOutOfBoundsException("Index: " + index + ", Size: " + listSize);
        }

        Node newNode = new Node(data); // Create new node
        Node current = head; // Start at head

        // Traverse to the node just before the specified index
        for (int i = 0; i < index; i++) {
            current = current.getNext();
        }

        newNode.setNext(current.getNext()); // Link new node into the list
        current.setNext(newNode); // Update the next pointer of the current node
        listSize++; // Increment size
    }

    public Object get(int index) {
        if (index < 0 || index >= listSize) {
            throw new IndexOutOfBoundsException("Index: " + index + ", Size: " + listSize);
        }

        Node current = head.getNext(); // Start from the first actual node

        // Traverse to the specified index
        for (int i = 0; i < index; i++) {
            current = current.getNext();
        }

        return current.getData(); // Return the data of the node at the index
    }

    public boolean remove(int index) {
        if (index < 0 || index >= listSize) {
            throw new IndexOutOfBoundsException("Index: " + index + ", Size: " + listSize);
        }

        Node current = head; // Start at head

        // Traverse to the node just before the specified index
        for (int i = 0; i < index; i++) {
            current = current.getNext();
        }

        Node toRemove = current.getNext(); // Node to be removed
        current.setNext(toRemove.getNext()); // Bypass the node to be removed
        listSize--; // Decrement size
        return true; // Successfully removed
    }

    public int size() {
        return listSize; // Return current size
    }
}
