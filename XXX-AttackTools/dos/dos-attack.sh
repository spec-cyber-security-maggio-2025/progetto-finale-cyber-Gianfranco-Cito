
#!/bin/bash


URL="http://cyber.blog:8000/articles/search?query=test"
for i in {1..50}
do
  curl -s $URL
done

