css 		= File.new('all.css', 'r')
blocks 		= []
new_css 	= ''
add_flg 	= true
while(line = css.gets)
	if line =~ /\{$/
		block = line.gsub(/\{/, '')
		if blocks.include? block
			add_flg = false
		else
			blocks << block
		end
	elsif (line =~ /\}$/ && add_flg == false) 
		add_flg = true
		line = line.sub(/\}$/, '')
	end

	if add_flg
		new_css += line
	end
end

File.open('all_fixed.css', 'w') {|f| f.write(new_css.gsub(/\n{3,}/, "\n")) }
